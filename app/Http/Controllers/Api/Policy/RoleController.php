<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Policy;

use App\DTO\RoleCollectionDTO;
use App\DTO\RoleDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Policy\StoreRoleRequest;
use App\Http\Requests\Policy\UpdateRoleRequest;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
{
    // Список ролей.
    public function index(): JsonResponse
    {
        $roles = Role::query()->orderBy('id')->get();

        $items = $roles->map(
            fn (Role $role): RoleDTO => $this->toDTO($role)
        )->all();

        $dto = new RoleCollectionDTO(
            items: $items,
            total: count($items),
        );

        return response()->json($dto->toArray(), 200);
    }

    // Просмотр одной роли.
    public function show(int $role): JsonResponse
    {
        $model = Role::query()->find($role);

        if ($model === null) {
            return response()->json(['error' => 'Role not found.'], 404);
        }

        return response()->json($this->toDTO($model)->toArray(), 200);
    }

    // Создание новой роли.
    public function store(StoreRoleRequest $request): JsonResponse
    {
        $dto = $request->toDTO();

        $model = DB::transaction(function () use ($dto): Role {
            return Role::query()->create([
                'name'        => $dto->name,
                'slug'        => $dto->slug,
                'description' => $dto->description,
                'created_by'  => $dto->createdBy,
            ]);
        });

        return response()->json($this->toDTO($model)->toArray(), 201);
    }

    // Обновление роли.
    public function update(UpdateRoleRequest $request, int $role): JsonResponse
    {
        $model = Role::query()->find($role);

        if ($model === null) {
            return response()->json(['error' => 'Role not found.'], 404);
        }

        $dto = $request->toDTO();

        DB::transaction(function () use ($model, $dto): void {
            $model->fill([
                'name'        => $dto->name,
                'slug'        => $dto->slug,
                'description' => $dto->description,
            ]);
            $model->save();
        });

        return response()->json($this->toDTO($model)->toArray(), 200);
    }

    // Мягкое удаление роли.
    public function destroy(Request $request, int $role): JsonResponse
    {
        $model = Role::query()->find($role);

        if ($model === null) {
            return response()->json(['error' => 'Role not found.'], 404);
        }

        /** @var mixed $actor */
        $actor = $request->attributes->get('__auth_user');

        DB::transaction(function () use ($model, $actor): void {
            $model->deleted_by = (int) ($actor->id ?? 0);
            $model->save();
            $model->delete();
        });

        return response()->json(['message' => 'Role soft deleted.'], 200);
    }

    // Восстановление мягко удаленной роли.
    public function restore(int $role): JsonResponse
    {
        $model = Role::query()->withTrashed()->find($role);

        if ($model === null) {
            return response()->json(['error' => 'Role not found.'], 404);
        }

        if (!$model->trashed()) {
            return response()->json(['error' => 'Role is not deleted.'], 400);
        }

        DB::transaction(function () use ($model): void {
            $model->restore();
            $model->deleted_by = null;
            $model->save();
        });

        return response()->json($this->toDTO($model)->toArray(), 200);
    }

    // Преобразование модели в DTO.
    private function toDTO(Role $role): RoleDTO
    {
        return new RoleDTO(
            id: (int) $role->id,
            name: (string) $role->name,
            slug: (string) $role->slug,
            description: $role->description,
            createdAt: $role->created_at,
            createdBy: (int) $role->created_by,
            deletedAt: $role->deleted_at,
            deletedBy: $role->deleted_by !== null ? (int) $role->deleted_by : null,
        );
    }
}
