<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

class PermissionRole extends Pivot
{
    use SoftDeletes;

    protected $table = 'permission_role';

    public $incrementing = true;

    public $timestamps = false;

    protected $fillable = [
        'role_id',
        'permission_id',
        'created_by',
        'deleted_by',
    ];
}
