<?php

declare(strict_types=1);

namespace App\DTO;

final class RolePermissionCollectionDTO
{
    /**
     * @param RolePermissionDTO[] $items
     */
    public function __construct(
        public int $roleId,
        public array $items,
    ) {}

    public function toArray(): array
    {
        return [
            'role_id' => $this->roleId,
            'permissions' => array_map(
                fn (RolePermissionDTO $item): array => $item->toArray(),
                $this->items,
            ),
        ];
    }
}