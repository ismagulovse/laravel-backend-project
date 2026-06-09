<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Policy;

use App\DTO\PermissionCollectionDTO;
use App\DTO\PermissionDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Policy\StorePermissionRequest;
use App\Http\Requests\Policy\UpdatePermissionRequest;
use App\Models\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    // Список разрешений.
    public function index(): JsonResponse
    {
        $permissions = Permission::query()->orderBy('id')->get();

        $items = $permissions->map(
            fn (Permission $permission): PermissionDTO => $this->toDTO($permission)
        )->all();

        $dto = new PermissionCollectionDTO(
            items: $items,
            total: count($items),
        );

        return response()->json($dto->toArray(), 200);
    }

    // Просмотр одного разрешения.
    public function show(int $permission): JsonResponse
    {
        $model = Permission::query()->find($permission);

        if ($model === null) {
            return response()->json(['error' => 'Permission not found.'], 404);
        }

        return response()->json($this->toDTO($model)->toArray(), 200);
    }

    // Создание нового разрешения.
    public function store(StorePermissionRequest $request): JsonResponse
    {
        $dto = $request->toDTO();

        $model = Permission::query()->create([
            'name' => $dto->name,
            'slug' => $dto->slug,
            'description' => $dto->description,
            'created_by' => $dto->createdBy,
        ]);

        return response()->json($this->toDTO($model)->toArray(), 201);
    }

    // Обновление разрешения.
    public function update(UpdatePermissionRequest $request, int $permission): JsonResponse
    {
        $model = Permission::query()->find($permission);

        if ($model === null) {
            return response()->json(['error' => 'Permission not found.'], 404);
        }

        $dto = $request->toDTO();

        $model->fill([
            'name' => $dto->name,
            'slug' => $dto->slug,
            'description' => $dto->description,
        ]);
        $model->save();

        return response()->json($this->toDTO($model)->toArray(), 200);
    }

    // Мягкое удаление разрешения.
    public function destroy(Request $request, int $permission): JsonResponse
    {
        $model = Permission::query()->find($permission);

        if ($model === null) {
            return response()->json(['error' => 'Permission not found.'], 404);
        }

        $actor = $request->attributes->get('__auth_user');

        $model->deleted_by = (int) ($actor->id ?? 0);
        $model->save();
        $model->delete();

        return response()->json(['message' => 'Permission soft deleted.'], 200);
    }

    // Восстановление мягко удаленного разрешения.
    public function restore(int $permission): JsonResponse
    {
        $model = Permission::query()->withTrashed()->find($permission);

        if ($model === null) {
            return response()->json(['error' => 'Permission not found.'], 404);
        }

        if (!$model->trashed()) {
            return response()->json(['error' => 'Permission is not deleted.'], 400);
        }

        $model->restore();
        $model->deleted_by = null;
        $model->save();

        return response()->json($this->toDTO($model)->toArray(), 200);
    }

    // Преобразование модели в DTO.
    private function toDTO(Permission $permission): PermissionDTO
    {
        return new PermissionDTO(
            id: (int) $permission->id,
            name: (string) $permission->name,
            slug: (string) $permission->slug,
            description: $permission->description,
            createdAt: $permission->created_at,
            createdBy: (int) $permission->created_by,
            deletedAt: $permission->deleted_at,
            deletedBy: $permission->deleted_by !== null ? (int) $permission->deleted_by : null,
        );
    }
}
