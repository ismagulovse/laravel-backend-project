<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\DeploymentFailedException;
use App\Exceptions\DeploymentInProgressException;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;

class DeploymentService implements DeploymentServiceInterface
{
    // Имя ключа блокировки в кэше.
    private const LOCK_KEY = 'git-deployment-lock';

    /**
     * Фабрика процессов. Вынесена в свойство, чтобы её можно было подменить
     * в тестах (Open/Closed) и не зависеть от конкретного способа запуска команд.
     *
     * @var callable(list<string>): Process
     */
    private $processFactory;

    /**
     * @param LoggerInterface                            $logger         Логгер деплоя (DIP)
     * @param CacheFactory                               $cache          Фабрика кэша для блокировки
     * @param callable(list<string>): Process|null       $processFactory Фабрика git-процессов
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly CacheFactory $cache,
        ?callable $processFactory = null,
    ) {
        // По умолчанию создаём реальный Symfony Process в директории репозитория.
        $this->processFactory = $processFactory ?? function (array $command): Process {
            return new Process($command, $this->repositoryPath());
        };
    }

    /**
     * {@inheritdoc}
     */
    public function deploy(?string $clientIp = null): array
    {
        $branch = (string) config('git.default_branch');

        // Логируем начало операции ДО любых действий (TC-11). Секрет здесь не фигурирует.
        $this->logger->info('Деплой запущен', [
            'ip'     => $clientIp,
            'branch' => $branch,
            'status' => 'started',
        ]);

        $lock = $this->cache->store()->lock(self::LOCK_KEY, $this->lockTimeout());

        // Пытаемся захватить блокировку без ожидания: если занята — деплой уже идёт.
        if (! $lock->get()) {
            $this->logger->warning('Деплой отклонён: уже выполняется', [
                'ip'     => $clientIp,
                'status' => 'in_progress',
            ]);

            throw new DeploymentInProgressException();
        }

        try {
            return $this->runDeployment($branch, $clientIp);
        } finally {
            // Блокировка снимается всегда — и при успехе, и при ошибке (TC-14).
            $lock->release();
        }
    }

    /**
     * Выполнить последовательность git-команд под захваченной блокировкой.
     *
     * @return array<string, mixed>
     */
    private function runDeployment(string $branch, ?string $clientIp): array
    {
        $this->ensureGitRepository();

        $commands = [
            ['git', 'checkout', $branch],
            ['git', 'reset', '--hard', 'HEAD'],
            ['git', 'pull', 'origin', $branch],
        ];

        $executed = [];

        foreach ($commands as $command) {
            $executed[] = $this->runCommand($command);
        }

        // Финальный лог об успешном завершении (TC-20).
        $this->logger->info('Деплой завершён', [
            'ip'     => $clientIp,
            'branch' => $branch,
            'status' => 'success',
        ]);

        return [
            'status'   => 'success',
            'branch'   => $branch,
            'commands' => $executed,
        ];
    }

    /**
     * Проверить, что директория является git-репозиторием (TC-17).
     */
    private function ensureGitRepository(): void
    {
        if (! is_dir($this->repositoryPath() . DIRECTORY_SEPARATOR . '.git')) {
            $this->logger->error('Деплой провален: директория не является git-репозиторием', [
                'path'   => $this->repositoryPath(),
                'status' => 'failed',
            ]);

            throw new DeploymentFailedException('Not a git repository');
        }
    }

    /**
     * Запустить одну git-команду и залогировать её результат.
     *
     * @param list<string> $command
     *
     * @return array<string, string> Описание выполненной команды
     */
    private function runCommand(array $command): array
    {
        $printable = implode(' ', $command);

        $this->logger->info('Выполняется команда', ['command' => $printable]);

        $process = ($this->processFactory)($command);
        $process->run();

        if (! $process->isSuccessful()) {
            $this->logger->error('Команда завершилась с ошибкой', [
                'command' => $printable,
                'output'  => trim($process->getErrorOutput() . $process->getOutput()),
                'status'  => 'failed',
            ]);

            throw new DeploymentFailedException(
                sprintf('Git command failed: %s', $printable)
            );
        }

        $output = trim($process->getOutput());

        $this->logger->info('Команда выполнена успешно', [
            'command' => $printable,
            'output'  => $output,
        ]);

        return [
            'command' => $printable,
            'output'  => $output,
        ];
    }

    private function repositoryPath(): string
    {
        return (string) config('git.repository_path', base_path());
    }

    private function lockTimeout(): int
    {
        return (int) config('git.lock_timeout', 300);
    }
}
