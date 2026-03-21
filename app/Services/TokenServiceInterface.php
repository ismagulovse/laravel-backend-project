<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Token;
use App\Models\User;

interface TokenServiceInterface
{
    
    public function createTokenPair(User $user): array;

    public function validateAccessToken(string $token): ?Token;

    public function validateRefreshToken(string $token): ?Token;

    public function revokeToken(Token $token): void;

    public function revokeAllUserTokens(User $user): void;

    public function refreshTokenPair(string $refreshToken): array;
}