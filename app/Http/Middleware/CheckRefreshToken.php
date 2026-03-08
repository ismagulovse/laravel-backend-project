<?php

// app/Http/Middleware/CheckRefreshToken.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRefreshToken
{
    /**
     * Проверяет наличие и валидность refresh токена.
     * Логика будет реализована на этапе работы с токенами.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        return $next($request);
    }
}