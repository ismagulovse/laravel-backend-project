<?php

declare(strict_types=1);

namespace App\DTO;

use App\Models\ChangeLog;

class ChangeLogDTO
{
    /**
     * @param int    $id
     * @param string $entity_type
     * @param int    $entity_id
     * @param array  $changed_fields  Только изменившиеся поля: ['field' => ['old' => ..., 'new' => ...]]
     * @param string $created_at
     * @param int    $created_by
     */
    public function __construct(
        public readonly int    $id,
        public readonly string $entity_type,
        public readonly int    $entity_id,
        public readonly array  $changed_fields,
        public readonly string $created_at,
        public readonly int    $created_by,
    ) {}

    /**
     * Создать DTO из модели.
     * Вычисляет diff между before и after — в changed_fields попадают только изменившиеся поля.
     *
     * @param ChangeLog $log
     * @return self
     */
    public static function fromModel(ChangeLog $log): self
    {
        return new self(
            id:             (int) $log->id,
            entity_type:    $log->entity_type,
            entity_id:      (int) $log->entity_id,
            changed_fields: self::diff($log->before ?? [], $log->after ?? []),
            created_at:     $log->created_at->toDateTimeString(),
            created_by:     (int) $log->created_by,
        );
    }

    /**
     * Вычислить разницу между состоянием ДО и ПОСЛЕ.
     * Возвращает только поля, которые изменились.
     *
     * @param array $before
     * @param array $after
     * @return array  ['field' => ['old' => value, 'new' => value]]
     */
    private static function diff(array $before, array $after): array
    {
        $allKeys = array_unique(array_merge(array_keys($before), array_keys($after)));
        $changed = [];

        foreach ($allKeys as $key) {
            $oldValue = $before[$key] ?? null;
            $newValue = $after[$key] ?? null;

            if ($oldValue !== $newValue) {
                $changed[$key] = ['old' => $oldValue, 'new' => $newValue];
            }
        }

        return $changed;
    }
}
