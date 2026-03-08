<?php

use App\Http\Controllers\Api\Auth\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function (): void {

    // Открытый маршрут — доступен всем без исключений
    Route::post('login', [AuthController::class, 'login']);

    // Только для неавторизованных пользователей
    Route::middleware('guest.check')->group(function (): void {
        Route::post('register', [AuthController::class, 'register']);
    });

    // Только для авторизованных пользователей (валидный access токен)
    Route::middleware('auth.check')->group(function (): void {
        Route::get('me',       [AuthController::class, 'me']);
        Route::post('out',     [AuthController::class, 'out']);
        Route::get('tokens',   [AuthController::class, 'tokens']);
        Route::post('out_all', [AuthController::class, 'outAll']);
    });

    // Только с валидным refresh токеном
    Route::middleware('refresh.check')->group(function (): void {
        Route::post('refresh', [AuthController::class, 'refresh']);
    });
});