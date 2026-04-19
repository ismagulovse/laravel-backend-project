<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\TokenService;
use App\Services\TokenServiceInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    
    public function register(): void
    {
        $this->app->bind(
            TokenServiceInterface::class,
            TokenService::class,
        );
    }

    public function boot(): void {}
}