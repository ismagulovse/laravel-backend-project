<?php

declare(strict_types=1);

namespace App\DTO;

final class PermissionCollectionDTO
{
    
    public function __construct(
        public readonly array $items,
        public readonly int $total,
    ) {}

    public function toArray(): array
    {
        return [
            'items' => array_map(
                static fn (PermissionDTO $permission): array => $permission->toArray(),
                $this->items
            ),
            'meta' => [
                'total' => $this->total,
            ],
        ];
    }
}
