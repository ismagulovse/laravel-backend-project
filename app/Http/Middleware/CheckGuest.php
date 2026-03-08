<?php

// app/Http/Middleware/CheckGuest.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckGuest
{
    /**
     * Блокирует доступ авторизованным пользователям.
     * Логика будет реализована на этапе работы с токенами.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        return $next($request);
    }
}