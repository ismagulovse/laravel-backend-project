<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'auth.check'    => \App\Http\Middleware\CheckAuth::class,
            'guest.check'   => \App\Http\Middleware\CheckGuest::class,
            'refresh.check' => \App\Http\Middleware\CheckRefreshToken::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
    $exceptions->render(function (\Throwable $e, $request) {
        return response()->json([
            'error'   => $e->getMessage(),
            'file'    => $e->getFile(),
            'line'    => $e->getLine(),
        ], 500);
    });
})->create();

