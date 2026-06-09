<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Database\Eloquent\Model;

interface AuditServiceInterface
{
    /**
     * Записать лог изменения сущности.
     *
     * @param Model $model    Изменённая модель
     * @param array $before   Состояние ДО изменения
     * @param array $after    Состояние ПОСЛЕ изменения
     * @param int   $actorId  ID пользователя, инициировавшего изменение
     */
    public function log(Model $model, array $before, array $after, int $actorId): void;
}
