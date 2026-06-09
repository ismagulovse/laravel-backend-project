<?php

declare(strict_types=1);

namespace App\DTO;

use Illuminate\Support\Collection;

class ChangeLogCollectionDTO
{
    
    public function __construct(
        public readonly array $items,
        public readonly int   $total,
    ) {}

    /**
     * Создать коллекцию DTO из Eloquent-коллекции логов.
     */
    public static function fromCollection(Collection $logs): self
    {
        $items = $logs->map(fn ($log) => ChangeLogDTO::fromModel($log))->all();

        return new self(
            items: $items,
            total: count($items),
        );
    }
}
