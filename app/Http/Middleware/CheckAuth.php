<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckAuth
{
    /**
     * Проверяет наличие и валидность access токена.
     * Логика будет реализована на этапе работы с токенами.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        return $next($request);
    }
}