<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Role;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;

class CheckPermission
{
    /**
     * Проверить, есть ли у пользователя хотя бы одно из требуемых разрешений (логика "ИЛИ").
     *
     * @param Request $request
     * @param Closure $next
     * @param string  ...$permissionSlugs  Один или несколько slug разрешений из маршрута
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string ...$permissionSlugs): mixed
    {
        /** @var mixed $actor */
        $actor = $request->attributes->get('__auth_user');

        if ($actor !== null) {
            $allowed = User::query()
                ->whereKey((int) $actor->id)
                ->whereHas('roles.permissions', fn ($query) => $query->whereIn('slug', $permissionSlugs))
                ->exists();
        } else {
            // Для неавторизованных считаем, что действует роль guest.
            $allowed = Role::query()
                ->where('slug', 'guest')
                ->whereHas('permissions', fn ($query) => $query->whereIn('slug', $permissionSlugs))
                ->exists();
        }

        if (!$allowed) {
            return response()->json([
                'error' => 'Access denied. Required permission: ' . implode(' or ', $permissionSlugs),
            ], 403);
        }

        return $next($request);
    }
}
