<?php

declare(strict_types=1);

namespace App\DTO;

final class AuthSuccessDTO
{

    public function __construct(
        public readonly string  $accessToken,
        public readonly string  $refreshToken,
        public readonly UserDTO $user,
    ) {}

    public function toArray(): array
    {
        return [
            'access_token'  => $this->accessToken,
            'refresh_token' => $this->refreshToken,
            'user'          => $this->user->toArray(),
        ];
    }
}