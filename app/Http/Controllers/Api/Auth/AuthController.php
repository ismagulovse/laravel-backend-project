<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    /**
     * Авторизация пользователя.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        return response()->json(['message' => 'login'], 200);
    }

    /**
     * Регистрация нового пользователя.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        return response()->json(['message' => 'register'], 201);
    }

    /**
     * Получение данных авторизованного пользователя.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json(['message' => 'me'], 200);
    }

    /**
     * Разлогинивание (отзыв текущего токена).
     */
    public function out(Request $request): JsonResponse
    {
        return response()->json(['message' => 'out'], 200);
    }

    /**
     * Список активных токенов пользователя.
     */
    public function tokens(Request $request): JsonResponse
    {
        return response()->json(['message' => 'tokens'], 200);
    }

    /**
     * Разлогинивание со всех устройств.
     */
    public function outAll(Request $request): JsonResponse
    {
        return response()->json(['message' => 'out_all'], 200);
    }

    /**
     * Обновление пары токенов по refresh токену.
     */
    public function refresh(Request $request): JsonResponse
    {
        return response()->json(['message' => 'refresh'], 200);
    }
}