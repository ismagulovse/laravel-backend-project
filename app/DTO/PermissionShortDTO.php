<?php

declare(strict_types=1);

namespace App\DTO;

final class PermissionShortDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public ?string $description,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
        ];
    }
}