<?php

declare(strict_types=1);

namespace App\DTO;

final class UserPermissionsDTO
{
    /**
     * @param PermissionShortDTO[] $permissions
     */
    public function __construct(
        public int $userId,
        public array $permissions,
    ) {}

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'permissions' => array_map(
                fn (PermissionShortDTO $permission): array => $permission->toArray(),
                $this->permissions,
            ),
        ];
    }
}