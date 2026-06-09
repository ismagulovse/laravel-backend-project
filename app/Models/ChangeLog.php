<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChangeLog extends Model
{
    // Таблица не имеет updated_at — логи неизменяемы.
    const UPDATED_AT = null;

    protected $table = 'change_logs';

    protected $fillable = [
        'entity_type',
        'entity_id',
        'before',
        'after',
        'created_by',
    ];

    protected $casts = [
        'before' => 'array',
        'after'  => 'array',
    ];

    /**
     * Пользователь, инициировавший изменение.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
