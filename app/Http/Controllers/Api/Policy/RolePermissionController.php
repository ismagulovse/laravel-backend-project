<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Policy;

use App\DTO\RolePermissionCollectionDTO;
use App\DTO\RolePermissionDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Policy\AttachPermissionRoleRequest;
use App\Models\Permission;
use App\Models\PermissionRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RolePermissionController extends Controller
{
    public function listRolePermissions(int $role): JsonResponse
    {
        $links = PermissionRole::query()
            ->where('role_id', $role)
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->get();

        $permissionMap = Permission::query()
            ->whereIn('id', $links->pluck('permission_id')->all())
            ->get(['id', 'name', 'slug'])
            ->keyBy('id');

        $items = $links->map(function (PermissionRole $link) use ($permissionMap): RolePermissionDTO {
            $permission = $permissionMap->get((int) $link->permission_id);

            return $this->toDTO($link, $permission);
        })
            ->values()
            ->all();

        $dto = new RolePermissionCollectionDTO(
            roleId: $role,
            items: $items,
        );

        return response()->json($dto->toArray(), 200);
    }

    public function showRolePermission(int $role, int $permission): JsonResponse
    {
        $link = PermissionRole::query()
            ->withTrashed()
            ->where('role_id', $role)
            ->where('permission_id', $permission)
            ->first();

        if ($link === null) {
            return response()->json(['error' => 'Role-permission link not found.'], 404);
        }

        $permissionModel = Permission::query()
            ->whereKey($link->permission_id)
            ->first(['id', 'name', 'slug']);

        return response()->json($this->toDTO($link, $permissionModel)->toArray(), 200);
    }

    public function attach(AttachPermissionRoleRequest $request): JsonResponse
    {
        $dto = $request->toDTO();
        $actorId = $this->resolveActorId($request);
        //ищем сущ. свзяь чтобы новую не делать 
        $link = PermissionRole::query()
            ->withTrashed()
            ->where('role_id', $dto['role_id'])
            ->where('permission_id', $dto['permission_id'])
            ->first();
        //если связь удалено уже, мы ее мягко восстановили
        if ($link !== null && $link->trashed()) {
            $link->restore();
            $link->deleted_by = null;
            $link->save();

            return response()->json(['message' => 'Permission attached to role (restored).'], 200);
        }

        //если связь активна и не нуждается в создании
        if ($link !== null) {
            return response()->json(['message' => 'Permission already attached to role.'], 200);
        }
        //создаем связь если мы прошли все if, получается что связи нет
        PermissionRole::query()->create([
            'role_id' => $dto['role_id'],
            'permission_id' => $dto['permission_id'],
            'created_by' => $actorId,
        ]);

        return response()->json(['message' => 'Permission attached to role.'], 201);
    }

    //просто вызываем мягкое удаление
    public function detach(Request $request, int $role, int $permission): JsonResponse
    {
        return $this->softDelete($request, $role, $permission);
    }

    public function softDelete(Request $request, int $role, int $permission): JsonResponse
    {
        //ищем нужную связь при этои игнорируем неактивную
        $link = PermissionRole::query()
            ->where('role_id', $role)
            ->where('permission_id', $permission)
            ->whereNull('deleted_at')
            ->first();
        //если связи нет то удаляеть нечего
        if ($link === null) {
            return response()->json(['error' => 'Role-permission link not found.'], 404);
        }

        $link->deleted_by = $this->resolveActorId($request);
        $link->save();
        $link->delete();

        return response()->json(['message' => 'Permission detached from role.'], 200);
    }

    public function restore(int $role, int $permission): JsonResponse
    {   //ищем нужнаую нам связь
        $link = PermissionRole::query()
            ->withTrashed()
            ->where('role_id', $role)
            ->where('permission_id', $permission)
            ->first();
        //если не нашли
        if ($link === null) {
            return response()->json(['error' => 'Role-permission link not found.'], 404);
        }
        //если связь активная
        if (!$link->trashed()) {
            return response()->json(['error' => 'Role-permission link is not deleted.'], 400);
        }

        $link->restore();
        $link->deleted_by = null;
        $link->save();

        return response()->json(['message' => 'Role-permission link restored.'], 200);
    }

    private function toDTO(PermissionRole $link, ?Permission $permission): RolePermissionDTO
    {
        return new RolePermissionDTO(
            id: (int) $link->id,
            roleId: (int) $link->role_id,
            permissionId: (int) $link->permission_id,
            permissionName: $permission?->name,
            permissionSlug: $permission?->slug,
            createdBy: (int) $link->created_by,
            deletedAt: $link->deleted_at,
            deletedBy: $link->deleted_by !== null ? (int) $link->deleted_by : null,
        );
    }
    //получаем пользователя
    private function resolveActorId(Request $request): int
    {
        
        $actor = $request->attributes->get('__auth_user');

        return (int) ($actor->id ?? 0);
    }
}