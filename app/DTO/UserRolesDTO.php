<?php

declare(strict_types=1);

namespace App\DTO;

final class UserRolesDTO
{
    
    public function __construct(
        public int $userId,
        public array $roles,
    ) {}

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'roles' => array_map(
                fn (RoleShortDTO $role): array => $role->toArray(),
                $this->roles,
            ),
        ];
    }
}