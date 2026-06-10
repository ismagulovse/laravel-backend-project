<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Observers\PermissionObserver;
use App\Observers\RoleObserver;
use App\Observers\UserObserver;
use App\Services\AuditService;
use App\Services\AuditServiceInterface;
use App\Services\DeploymentService;
use App\Services\DeploymentServiceInterface;
use App\Services\TokenService;
use App\Services\TokenServiceInterface;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            TokenServiceInterface::class,
            TokenService::class,
        );

        // Привязываем интерфейс к реализации — DIP из SOLID.
        // Теперь везде где нужен AuditServiceInterface, Laravel автоматически подставит AuditService.
        $this->app->bind(
            AuditServiceInterface::class,
            AuditService::class,
        );

        $this->app->bind(
            DeploymentServiceInterface::class,
            fn ($app) => new DeploymentService(
                Log::channel('deployment'),
                $app->make(CacheFactory::class),
            ),
        );
    }

    public function boot(): void
    {
        // Регистрируем обсерверы — они начнут слушать события моделей.
        User::observe(UserObserver::class);
        Role::observe(RoleObserver::class);
        Permission::observe(PermissionObserver::class);

    }
}
