<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\TokenServiceInterface;
use Closure;
use Illuminate\Http\Request;

class CheckAuth
{
    public function __construct(
        private readonly TokenServiceInterface $tokenService
    ) {}

    public function handle(Request $request, Closure $next): mixed
    {
        $header = $request->header('Authorization', '');

        if (!str_starts_with($header, 'Bearer ')) {
            return response()->json(
                ['error' => 'Unauthorized. Token required.'],
                401
            );
        }

        $token = substr($header, 7);

        $tokenRecord = $this->tokenService->validateAccessToken($token);

        if ($tokenRecord === null) {
            return response()->json(
                ['error' => 'Unauthorized. Invalid or expired token.'],
                401
            );
        }

        $request->attributes->set('__auth_user', $tokenRecord->user);
        $request->attributes->set('__auth_token', $tokenRecord);

        return $next($request);
    }
}