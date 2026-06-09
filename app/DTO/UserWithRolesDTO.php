<?php

declare(strict_types=1);

namespace App\DTO;

final class UserWithRolesDTO
{
    /**
     * @param RoleShortDTO[] $roles
     */
    public function __construct(
        public int $id,
        public string $username,
        public string $email,
        public array $roles,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'roles' => array_map(
                fn (RoleShortDTO $role): array => $role->toArray(),
                $this->roles,
            ),
        ];
    }
}