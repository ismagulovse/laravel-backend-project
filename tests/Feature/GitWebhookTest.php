<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\DeploymentService;
use App\Services\DeploymentServiceInterface;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Support\Facades\Log;
use Mockery;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class GitWebhookTest extends TestCase
{
    private const VALID_SECRET = '123456789012345678901234567890123456';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('git.webhook_secret', self::VALID_SECRET);
        config()->set('git.default_branch', 'main');
        // Репозиторий по умолчанию — корень проекта, где реально есть .git.
        config()->set('git.repository_path', base_path());
    }

    /**
     * Подменить сервис деплоя так, чтобы git-команды не выполнялись по-настоящему,
     * а возвращали успешный замоканный процесс. Сохраняет порядок вызванных команд.
     *
     * @param list<list<string>> &$captured Сюда складываются вызванные команды
     */
    private function fakeSuccessfulDeployment(array &$captured): void
    {
        $factory = function (array $command) use (&$captured): Process {
            $captured[] = $command;

            $process = Mockery::mock(Process::class);
            $process->shouldReceive('run')->once();
            $process->shouldReceive('isSuccessful')->andReturnTrue();
            $process->shouldReceive('getOutput')->andReturn('ok');
            $process->shouldReceive('getErrorOutput')->andReturn('');

            return $process;
        };

        $this->app->bind(
            DeploymentServiceInterface::class,
            fn ($app) => new DeploymentService(
                Log::channel('deployment'),
                $app->make(CacheFactory::class),
                $factory,
            ),
        );
    }

    public function test_успешный_деплой_возвращает_200_и_json(): void
    {
        $captured = [];
        $this->fakeSuccessfulDeployment($captured);

        $response = $this->postJson('/api/hooks/git', ['secret_key' => self::VALID_SECRET]);

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonPath('result.status', 'success')
            ->assertJsonPath('message', 'Deployment finished');
    }

    public function test_команды_git_вызываются_в_правильном_порядке(): void
    {
        $captured = [];
        $this->fakeSuccessfulDeployment($captured);

        $this->postJson('/api/hooks/git', ['secret_key' => self::VALID_SECRET])->assertOk();

        $this->assertSame([
            ['git', 'checkout', 'main'],
            ['git', 'reset', '--hard', 'HEAD'],
            ['git', 'pull', 'origin', 'main'],
        ], $captured);
    }

    public function test_ветка_берётся_из_конфига(): void
    {
        config()->set('git.default_branch', 'develop');

        $captured = [];
        $this->fakeSuccessfulDeployment($captured);

        $this->postJson('/api/hooks/git', ['secret_key' => self::VALID_SECRET])->assertOk();

        $this->assertSame(['git', 'checkout', 'develop'], $captured[0]);
    }

    public function test_неверный_ключ_возвращает_403(): void
    {
        $response = $this->postJson('/api/hooks/git', [
            'secret_key' => self::VALID_SECRET . 'x',
        ]);

        $response->assertForbidden()
            ->assertJsonPath('message', 'Invalid secret key');
    }

    public function test_ключ_в_другом_регистре_возвращает_403(): void
    {
        config()->set('git.webhook_secret', 'AbCdEf123456789012345678901234567890');

        $response = $this->postJson('/api/hooks/git', [
            'secret_key' => 'abcdef123456789012345678901234567890',
        ]);

        $response->assertForbidden();
    }

    public function test_запрос_без_ключа_возвращает_403(): void
    {
        $this->postJson('/api/hooks/git', [])->assertForbidden();
    }

    public function test_конкурентный_запрос_возвращает_409(): void
    {
        // Вручную захватываем ту же блокировку, что использует сервис, — имитируем идущий деплой.
        $lock = $this->app->make(CacheFactory::class)->store()->lock('git-deployment-lock', 300);
        $this->assertTrue($lock->get());

        $captured = [];
        $this->fakeSuccessfulDeployment($captured);

        try {
            $response = $this->postJson('/api/hooks/git', ['secret_key' => self::VALID_SECRET]);

            $response->assertStatus(409)
                ->assertJsonPath('message', 'Deployment already in progress');

            // Команды git не должны были выполниться.
            $this->assertSame([], $captured);
        } finally {
            $lock->release();
        }
    }

    public function test_ошибка_git_команды_возвращает_500_и_снимает_блокировку(): void
    {
        $factory = function (array $command): Process {
            $process = Mockery::mock(Process::class);
            $process->shouldReceive('run')->once();
            $process->shouldReceive('isSuccessful')->andReturnFalse();
            $process->shouldReceive('getOutput')->andReturn('');
            $process->shouldReceive('getErrorOutput')->andReturn('fatal: error');

            return $process;
        };

        $this->app->bind(
            DeploymentServiceInterface::class,
            fn ($app) => new DeploymentService(
                Log::channel('deployment'),
                $app->make(CacheFactory::class),
                $factory,
            ),
        );

        $this->postJson('/api/hooks/git', ['secret_key' => self::VALID_SECRET])
            ->assertStatus(500);

        // Блокировка должна быть снята: можем захватить её снова.
        $lock = $this->app->make(CacheFactory::class)->store()->lock('git-deployment-lock', 300);
        $this->assertTrue($lock->get());
        $lock->release();
    }

    public function test_отсутствие_git_репозитория_возвращает_500(): void
    {
        // Указываем директорию без .git.
        config()->set('git.repository_path', sys_get_temp_dir());

        $captured = [];
        $this->fakeSuccessfulDeployment($captured);

        $this->postJson('/api/hooks/git', ['secret_key' => self::VALID_SECRET])
            ->assertStatus(500)
            ->assertJsonPath('message', 'Not a git repository');

        // До git-команд дело не дошло.
        $this->assertSame([], $captured);
    }

    public function test_секретный_ключ_не_попадает_в_лог(): void
    {
        Log::shouldReceive('channel')->andReturnSelf();
        Log::shouldReceive('info')->andReturnUsing(function (string $message, array $context = []): void {
            $this->assertStringNotContainsString(self::VALID_SECRET, json_encode($context, JSON_THROW_ON_ERROR));
        });
        Log::shouldReceive('warning')->andReturnNull();
        Log::shouldReceive('error')->andReturnNull();

        $captured = [];
        $this->fakeSuccessfulDeployment($captured);

        $this->postJson('/api/hooks/git', ['secret_key' => self::VALID_SECRET])->assertOk();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
