<?php

declare(strict_types=1);

namespace App\DTO;

use Illuminate\Support\Carbon;

final class UserRoleDTO
{
    public function __construct(
        public int $id,
        public int $userId,
        public int $roleId,
        public int $createdBy,
        public ?Carbon $deletedAt,
        public ?int $deletedBy,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'role_id' => $this->roleId,
            'created_by' => $this->createdBy,
            'deleted_at' => $this->deletedAt?->format('Y-m-d H:i:s'),
            'deleted_by' => $this->deletedBy,
            'is_deleted' => $this->deletedAt !== null,
        ];
    }
}