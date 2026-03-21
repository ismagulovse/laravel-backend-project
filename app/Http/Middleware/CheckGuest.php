<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\TokenServiceInterface;
use Closure;
use Illuminate\Http\Request;

class CheckGuest
{
    
    public function __construct(
        private readonly TokenServiceInterface $tokenService
    ) {}

    
    public function handle(Request $request, Closure $next): mixed
    {
        $header = $request->header('Authorization', '');

        if (str_starts_with($header, 'Bearer ')) {
            $token = substr($header, 7);

            if ($this->tokenService->validateAccessToken($token) !== null) {
                return response()->json(
                    ['error' => 'Already authenticated.'],
                    403
                );
            } 
        }

        return $next($request);
    }
}