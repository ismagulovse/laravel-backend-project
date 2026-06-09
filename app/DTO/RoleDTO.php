<?php

declare(strict_types=1);

namespace App\DTO;

use Carbon\Carbon;

final class RoleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $slug,
        public readonly ?string $description,
        public readonly Carbon $createdAt,
        public readonly int $createdBy,
        public readonly ?Carbon $deletedAt,
        public readonly ?int $deletedBy,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            // В API даты лучше отдавать строкой в одном формате.
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'created_by' => $this->createdBy,
            'deleted_at' => $this->deletedAt?->format('Y-m-d H:i:s'),
            'deleted_by' => $this->deletedBy,
        ];
    }
}
