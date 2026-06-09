<?php

declare(strict_types=1);

namespace App\DTO;

final class RoleCollectionDTO
{
    /**
     * @param RoleDTO[] $items
     */
    public function __construct(
        public readonly array $items,
        public readonly int $total,
    ) {}

    public function toArray(): array
    {
        return [
            // Набор ролей.
            'items' => array_map(
                static fn (RoleDTO $role): array => $role->toArray(),
                $this->items
            ),
            // Мета-данные (общее количество).
            'meta' => [
                'total' => $this->total,
            ],
        ];
    }
}
