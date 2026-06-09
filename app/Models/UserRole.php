<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserRole extends Pivot
{
    use SoftDeletes;

    protected $table = 'role_user';

    public $incrementing = true;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'role_id',
        'created_by',
        'deleted_by',
    ];
}
