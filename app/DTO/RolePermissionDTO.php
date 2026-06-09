<?php

declare(strict_types=1);

namespace App\DTO;

use Illuminate\Support\Carbon;

final class RolePermissionDTO
{
    public function __construct(
        public int $id,
        public int $roleId,
        public int $permissionId,
        public ?string $permissionName,
        public ?string $permissionSlug,
        public int $createdBy,
        public ?Carbon $deletedAt = null,
        public ?int $deletedBy = null,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'role_id' => $this->roleId,
            'permission_id' => $this->permissionId,
            'permission_name' => $this->permissionName,
            'permission_slug' => $this->permissionSlug,
            'created_by' => $this->createdBy,
            'deleted_at' => $this->deletedAt?->format('Y-m-d H:i:s'),
            'deleted_by' => $this->deletedBy,
            'is_deleted' => $this->deletedAt !== null,
        ];
    }
}