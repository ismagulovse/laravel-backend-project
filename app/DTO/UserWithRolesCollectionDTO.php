<?php

declare(strict_types=1);

namespace App\DTO;

final class UserWithRolesCollectionDTO
{
   
    public function __construct(
        public array $items,
    ) {}

    public function toArray(): array
    {
        return [
            'items' => array_map(
                fn (UserWithRolesDTO $item): array => $item->toArray(),
                $this->items,
            ),
        ];
    }
}