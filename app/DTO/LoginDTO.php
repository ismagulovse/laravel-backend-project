<?php

declare(strict_types=1);

namespace App\DTO;

final class LoginDTO
{
    public function __construct(
        public readonly string $username,
        public readonly string $password,
    ) {}
}