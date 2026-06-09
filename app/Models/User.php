<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory;

    protected $fillable = [
        'username',
        'email',
        'password',
        'birthday',
    ];

    protected $hidden = ['password', ];

    protected $casts = ['birthday' => 'date', ];


    public function tokens(): HasMany
    {
        return $this->hasMany(Token::class);
    }

    public function activeTokens(): HasMany
    {
        return $this->hasMany(Token::class)
            ->where('is_revoked', false)
            ->where('access_expires_at', '>', now());
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user')
            ->using(UserRole::class)
            ->withPivot(['id', 'created_by', 'deleted_at', 'deleted_by'])
            ->wherePivotNull('deleted_at');
    }
}
