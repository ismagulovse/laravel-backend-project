<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ChangeLog;
use Illuminate\Database\Eloquent\Model;

class AuditService implements AuditServiceInterface
{
    // Поля, которые не несут смысловой нагрузки для аудита и исключаются из логов.
    private const EXCLUDED_FIELDS = ['created_at', 'updated_at', 'deleted_at', 'password'];

    /**
     * Записать лог изменения сущности.
     */
    public function log(Model $model, array $before, array $after, int $actorId): void
    {
        ChangeLog::create([
            'entity_type' => $this->resolveEntityType($model),
            'entity_id'   => $model->getKey(),
            'before'      => $this->filterFields($before),
            'after'       => $this->filterFields($after),
            'created_by'  => $actorId,
        ]);
    }
    
    /**
     * Определить строковый тип сущности по классу модели.
     * Например, App\Models\User → 'user'.
     */
    private function resolveEntityType(Model $model): string
    {
        return strtolower(class_basename($model));
    }

    /**
     * Удалить служебные поля из снимка данных.
     */
    private function filterFields(array $data): array
    {
        return array_diff_key($data, array_flip(self::EXCLUDED_FIELDS));
    }
}
