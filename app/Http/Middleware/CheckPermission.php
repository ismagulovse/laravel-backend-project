<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Role;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;

class CheckPermission
{
    public function handle(Request $request, Closure $next, array $permissionSlug): mixed
    {
        /** @var mixed $actor */
        $actor = $request->attributes->get('__auth_user');

        if ($actor !== null) {
            dd($permissionSlug);
            $allowed = User::query()
                ->whereKey((int) $actor->id)
                ->whereHas('roles.permissions', fn ($query) => $query->where('slug', $permissionSlug))
                ->exists();
        } else {
            // Для неавторизованных считаем, что действует роль guest.
            $allowed = Role::query()
                ->where('slug', 'guest')
                ->whereHas('permissions', fn ($query) => $query->where('slug', $permissionSlug))
                ->exists();
        }

        if (!$allowed) {
            return response()->json([
                'error' => 'Access denied. Required permission: ' . $permissionSlug,
            ], 403);
        }

        return $next($request);
    }
}
