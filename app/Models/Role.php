<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'created_by',
        'deleted_by',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'role_user')
            ->using(UserRole::class)
            ->withPivot(['id', 'created_by', 'deleted_at', 'deleted_by'])
            ->wherePivotNull('deleted_at');
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'permission_role')
            ->using(PermissionRole::class)
            ->withPivot(['id', 'created_by', 'deleted_at', 'deleted_by'])
            ->wherePivotNull('deleted_at');
    }
}
