<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\DTO\ChangeLogCollectionDTO;
use App\DTO\ChangeLogDTO;
use App\Http\Controllers\Controller;
use App\Models\ChangeLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChangeLogController extends Controller
{
    // Соответствие slug сущности → класс модели → требуемое разрешение.
    private const ENTITY_MAP = [
        'user'       => ['model' => User::class,       'permission' => 'get-story-user'],
        'role'       => ['model' => Role::class,       'permission' => 'get-story-role'],
        'permission' => ['model' => Permission::class, 'permission' => 'get-story-permission'],
    ];

    /**
     * Получить историю изменений пользователя.
     */
    public function userStory(Request $request, User $user): JsonResponse
    {
        return $this->story($request, 'user', (int) $user->id);
    }

    /**
     * Получить историю изменений роли.
     
     */
    public function roleStory(Request $request, Role $role): JsonResponse
    {
        return $this->story($request, 'role', (int) $role->id);
    }

    /**
     * Получить историю изменений разрешения.
     */
    public function permissionStory(Request $request, Permission $permission): JsonResponse
    {
        return $this->story($request, 'permission', (int) $permission->id);
    }

    /**
     * Восстановить сущность к состоянию ДО мутации из конкретной записи лога.
     * Сам факт восстановления логируется автоматически обсервером (через save()).
     */
    public function restore(ChangeLog $log): JsonResponse
    {
        $entityConfig = self::ENTITY_MAP[$log->entity_type] ?? null;

        if ($entityConfig === null) {
            return response()->json(['message' => 'Unknown entity type.'], 422);
        }

        $modelClass = $entityConfig['model'];

        $entity = $modelClass::withTrashed()->find($log->entity_id);

        if ($entity === null) {
            return response()->json(['message' => 'Entity not found.'], 404);
        }

        $targetState = $log->before;

        if (empty($targetState)) {
            return response()->json(['message' => 'Nothing to restore: before state is empty.'], 422);
        }

        DB::transaction(function () use ($entity, $targetState): void {
            // Исключаем служебные поля из восстановления.
            $restorable = array_diff_key($targetState, array_flip(['id', 'created_at', 'password']));
            $entity->fill($restorable)->save();
        });

        return response()->json(['message' => 'Entity restored successfully.']);
    }

    /**
     * Общий метод получения истории для любой сущности.
     * Проверяет разрешение и возвращает коллекцию DTO.
     */
    private function story(Request $request, string $entityType, int $entityId): JsonResponse
    {
        $config = self::ENTITY_MAP[$entityType];
        $requiredPermission = $config['permission'];

        $user = $request->attributes->get('__auth_user');

        $hasPermission = $user->roles()
            ->with('permissions')
            ->get()
            ->flatMap(fn ($role) => $role->permissions)
            ->contains('slug', $requiredPermission);

        if (!$hasPermission) {
            return response()->json([
                'message'             => 'Forbidden.',
                'required_permission' => $requiredPermission,
            ], 403);
        }

        $logs = ChangeLog::query()
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->orderByDesc('created_at')
            ->get();

        $collection = ChangeLogCollectionDTO::fromCollection($logs);

        return response()->json([
            'data'  => array_map(fn (ChangeLogDTO $dto) => (array) $dto, $collection->items),
            'total' => $collection->total,
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
}
