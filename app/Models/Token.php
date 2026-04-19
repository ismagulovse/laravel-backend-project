<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Token extends Model
{
    protected $fillable = [
        'user_id',
        'access_token_hash',
        'refresh_token_hash',
        'is_revoked',
        'refresh_used',
        'access_expires_at',
        'refresh_expires_at',
    ];

    protected $casts = [
        'is_revoked'         => 'boolean',
        'refresh_used'       => 'boolean',
        'access_expires_at'  => 'datetime',
        'refresh_expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}