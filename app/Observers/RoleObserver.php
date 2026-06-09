<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Role;
use App\Services\AuditServiceInterface;

class RoleObserver
{
    public function __construct(private readonly AuditServiceInterface $auditService) {}

    /**
     * Логировать создание роли.
     */
    public function created(Role $role): void
    {
        $this->auditService->log($role, [], $role->getAttributes(), $this->actorId());
    }

    /**
     * Логировать обновление роли.
     */
    public function updated(Role $role): void
    {
        $this->auditService->log($role, $role->getOriginal(), $role->getAttributes(), $this->actorId());
    }

    /**
     * Логировать мягкое удаление роли.
     */
    public function deleted(Role $role): void
    {
        $this->auditService->log($role, $role->getAttributes(), [], $this->actorId());
    }

    /**
     * Логировать восстановление роли.
     */
    public function restored(Role $role): void
    {
        $this->auditService->log($role, $role->getOriginal(), $role->getAttributes(), $this->actorId());
    }

    /**
     * Логировать жёсткое удаление роли.
     */
    public function forceDeleted(Role $role): void
    {
        $this->auditService->log($role, $role->getAttributes(), [], $this->actorId());
    }

    /**
     * ID текущего авторизованного пользователя.
     * Берём из request (__auth_user кладёт middleware CheckAuth).
     * Fallback = 1 (системный пользователь, например при сидах/в консоли).
     */
    private function actorId(): int
    {
        /** @var \App\Models\User|null $actor */
        $actor = request()->attributes->get('__auth_user');

        return (int) ($actor->id ?? 1);
    }
}
