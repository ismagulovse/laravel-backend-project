<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\TokenServiceInterface;
use Closure;
use Illuminate\Http\Request;

class CheckRefreshToken
{
    
    public function __construct(
        private readonly TokenServiceInterface $tokenService
    ) {}

    
    public function handle(Request $request, Closure $next): mixed
    {

        $refreshToken = $request->input('refresh_token');

        if (empty($refreshToken)) {
            return response()->json( ['error' => 'Refresh token required.'],401 );
        }

        $tokenRecord = $this->tokenService->validateRefreshToken($refreshToken);

        if ($tokenRecord === null) {
            return response()->json( ['error' => 'Invalid or expired refresh token.'],422 );
        }

        $request->attributes->set('__refresh_token_record', $tokenRecord);

        return $next($request);
    }
}