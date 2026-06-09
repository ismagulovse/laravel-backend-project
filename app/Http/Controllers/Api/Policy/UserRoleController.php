<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Policy;

use App\DTO\PermissionShortDTO;
use App\DTO\RoleShortDTO;
use App\DTO\UserPermissionsDTO;
use App\DTO\UserRoleDTO;
use App\DTO\UserRolesDTO;
use App\DTO\UserWithRolesCollectionDTO;
use App\DTO\UserWithRolesDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Policy\AttachUserRoleRequest;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserRoleController extends Controller
{
    public function indexUsers(): JsonResponse
    {
        //получаем пользователя с его ролями
        $users = User::query()
            ->with('roles')
            ->orderBy('id')
            ->get()
            ->map(fn (User $user): UserWithRolesDTO => $this->toUserWithRolesDTO($user))
            ->values()
            ->all();

        $dto = new UserWithRolesCollectionDTO(
            items: $users,
        );

        return response()->json($dto->toArray(), 200);
    }
    //получить роли пользователя 
    public function listUserRoles(int $user): JsonResponse
    {
        // Ищется пользователь по айди и сразу загружаются его роли.
        $model = User::query()->with('roles')->find($user);

        if ($model === null) {
            return response()->json(['error' => 'User not found.'], 404);
        }

        $roles = $model->roles
            ->map(fn ($role): RoleShortDTO => $this->toRoleShortDTO($role))
            ->values()
            ->all();

        $dto = new UserRolesDTO(
            userId: (int) $model->id,
            roles: $roles,
        );

        return response()->json($dto->toArray(), 200);
    }
    //получаем разрешения у пользоватеоля  
    public function listUserPermissions(int $user): JsonResponse
    {   
        //получаем через роли
        $model = User::query()->with('roles.permissions')->find($user);

        if ($model === null) {
            return response()->json(['error' => 'User not found.'], 404);
        }

        $permissions = $model->roles
            ->flatMap(fn ($role) => $role->permissions->map(
                fn ($permission): PermissionShortDTO => $this->toPermissionShortDTO($permission)
            ))
            ->unique(fn (PermissionShortDTO $permission): string => $permission->slug)
            ->sortBy(fn (PermissionShortDTO $permission): string => $permission->slug)
            ->values()
            ->all();

        $dto = new UserPermissionsDTO(
            userId: (int) $model->id,
            permissions: $permissions,
        );

        return response()->json($dto->toArray(), 200);
    }
    //посмотрели конкретную роль у пользователя
    public function showUserRole(int $user, int $role): JsonResponse
    {
        $link = UserRole::query()
            ->withTrashed()
            ->where('user_id', $user)
            ->where('role_id', $role)
            ->first();

        if ($link === null) {
            return response()->json(['error' => 'User-role link not found.'], 404);
        }

        return response()->json($this->toUserRoleDTO($link)->toArray(), 200);
    }

    //назначаем роль
    public function attach(AttachUserRoleRequest $request): JsonResponse
    {

        $dto = $request->toDTO();
        $actorId = $this->resolveActorId($request);
        $currentLinks = UserRole::query()
        ->where('user_id', $dto['user_id'])
        ->whereNull('deleted_at')
        ->where('role_id', '!=', $dto['role_id'])
        ->get();
        
        //удаляем все активные роли
        foreach ($currentLinks as $currentLink) {
            $currentLink->deleted_by = $actorId;
            $currentLink->save();
            $currentLink->delete();
        }
    //ищем нужную связь
        $link = UserRole::query()
            ->withTrashed()
            ->where('user_id', $dto['user_id'])
            ->where('role_id', $dto['role_id'])
            ->first();
        //если связь когда то была то мы ее восстановили
        if ($link !== null && $link->trashed()) {
            $link->restore();
            $link->deleted_by = null;
            $link->save();

            return response()->json(['message' => 'Role attached to user (restored).'], 200);
        }
        //ну а если роли нет то создаем
        if ($link === null) {
            UserRole::query()->create([
                'user_id' => $dto['user_id'],
                'role_id' => $dto['role_id'],
                'created_by' => $actorId,
            ]);

            return response()->json(['message' => 'Role assigned to user.'], 201);
        }
        //а если данная роль уже была то просто говорим об этом
        return response()->json(['message' => 'Role already assigned to user.'], 200);
    }

    public function detach(Request $request, int $user, int $role): JsonResponse
    {
        return $this->softDelete($request, $user, $role);
    }

    //функция чтобы мягко удалять
    public function softDelete(Request $request, int $user, int $role): JsonResponse
    {   
        //проучаем пользователя
        $actorId = $this->resolveActorId($request);
        //ищем нужную нам связьы
        $link = UserRole::query()
            ->where('user_id', $user)
            ->where('role_id', $role)
            ->whereNull('deleted_at')
            ->first();

        if ($link === null) {
            return response()->json(['error' => 'User-role link not found.'], 404);
        }

        $link->deleted_by = $actorId;
        $link->save();
        $link->delete();
        //чтобы пользователь не остался без роли
        $this->ensureGuestRole($user, $actorId);

        return response()->json(['message' => 'Role detached from user.'], 200);
    }

    public function restore(int $user, int $role): JsonResponse
    {
        $link = UserRole::query()
            ->withTrashed()
            ->where('user_id', $user)
            ->where('role_id', $role)
            ->first();

        if ($link === null) {
            return response()->json(['error' => 'User-role link not found.'], 404);
        }

        if (!$link->trashed()) {
            return response()->json(['error' => 'User-role link is not deleted.'], 400);
        }

        $link->restore();
        $link->deleted_by = null;
        $link->save();

        return response()->json(['message' => 'User-role link restored.'], 200);
    }

    private function toUserWithRolesDTO(User $user): UserWithRolesDTO
    {
        $roles = $user->roles
            ->map(fn ($role): RoleShortDTO => $this->toRoleShortDTO($role))
            ->values()
            ->all();

        return new UserWithRolesDTO(
            id: (int) $user->id,
            username: (string) $user->username,
            email: (string) $user->email,
            roles: $roles,
        );
    }

    private function toRoleShortDTO(Role $role): RoleShortDTO
    {
        return new RoleShortDTO(
            id: (int) $role->id,
            name: (string) $role->name,
            slug: (string) $role->slug,
        );
    }

    private function toPermissionShortDTO($permission): PermissionShortDTO
    {
        return new PermissionShortDTO(
            id: (int) $permission->id,
            name: (string) $permission->name,
            slug: (string) $permission->slug,
            description: $permission->description,
        );
    }

    private function toUserRoleDTO(UserRole $link): UserRoleDTO
    {
        return new UserRoleDTO(
            id: (int) $link->id,
            userId: (int) $link->user_id,
            roleId: (int) $link->role_id,
            createdBy: (int) $link->created_by,
            deletedAt: $link->deleted_at,
            deletedBy: $link->deleted_by !== null ? (int) $link->deleted_by : null,
        );
    }

    private function resolveActorId(Request $request): int
    {
      
        $actor = $request->attributes->get('__auth_user');

        return (int) ($actor->id ?? 0);
    }

    private function ensureGuestRole(int $userId, int $actorId): void
    {
        $hasActiveRole = UserRole::query()
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->exists();

        if ($hasActiveRole) {
            return;
        }

        $guestRole = Role::query()->withTrashed()->where('slug', 'guest')->first();

        if ($guestRole === null) {
            $guestRole = Role::query()->create([
                'name' => 'Guest',
                'slug' => 'guest',
                'description' => 'Read-only guest access.',
                'created_by' => $actorId,
            ]);
        } elseif ($guestRole->trashed()) {
            $guestRole->restore();
            $guestRole->deleted_by = null;
            $guestRole->save();
        }

        $guestLink = UserRole::query()
            ->withTrashed()
            ->where('user_id', $userId)
            ->where('role_id', (int) $guestRole->id)
            ->first();

        if ($guestLink === null) {
            UserRole::query()->create([
                'user_id' => $userId,
                'role_id' => (int) $guestRole->id,
                'created_by' => $actorId,
            ]);

            return;
        }

        if ($guestLink->trashed()) {
            $guestLink->restore();
            $guestLink->deleted_by = null;
            $guestLink->save();
        }
    }
}