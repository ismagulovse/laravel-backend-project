<?php

declare(strict_types=1);

namespace App\DTO;

use Carbon\Carbon;

final class RegisterDTO
{

    public function __construct(
        public readonly string $username,
        public readonly string $email,
        public readonly string $password,
        public readonly Carbon $birthday,
    ) {}
}