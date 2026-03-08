<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    /**
     * Авторизация пользователя.
     */
    public function login(): JsonResponse
    {
        return response()->json(['message' => 'login'], 200);
    }

    /**
     * Регистрация нового пользователя.
     */
    public function register(): JsonResponse
    {
        return response()->json(['message' => 'register'], 201);
    }

    /**
     * Получение данных авторизованного пользователя.
     */
    public function me(): JsonResponse
    {
        return response()->json(['message' => 'me'], 200);
    }

    /**
     * Разлогинивание (отзыв текущего токена).
     */
    public function out(): JsonResponse
    {
        return response()->json(['message' => 'out'], 200);
    }

    /**
     * Список активных токенов пользователя.
     */
    public function tokens(): JsonResponse
    {
        return response()->json(['message' => 'tokens'], 200);
    }

    /**
     * Разлогинивание со всех устройств.
     */
    public function outAll(): JsonResponse
    {
        return response()->json(['message' => 'out_all'], 200);
    }

    /**
     * Обновление пары токенов по refresh токену.
     */
    public function refresh(): JsonResponse
    {
        return response()->json(['message' => 'refresh'], 200);
    }
}