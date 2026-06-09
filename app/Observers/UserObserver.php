<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\User;
use App\Services\AuditServiceInterface;

class UserObserver
{
    public function __construct(private readonly AuditServiceInterface $auditService) {}

    /**
     * Логировать создание пользователя.
     * before = {} (пустой объект, записи ещё не было), after = новые данные.
     */
    public function created(User $user): void
    {
        $this->auditService->log($user, [], $user->getAttributes(), $this->actorId());
    }

    /**
     * Логировать обновление пользователя.
     * before = оригинальные значения до save(), after = текущие значения.
     */
    public function updated(User $user): void
    {
        $this->auditService->log($user, $user->getOriginal(), $user->getAttributes(), $this->actorId());
    }

    /**
     * Логировать мягкое удаление.
     * before = данные до удаления, after = {} (запись удалена).
     */
    public function deleted(User $user): void
    {
        $this->auditService->log($user, $user->getAttributes(), [], $this->actorId());
    }

    /**
     * Логировать восстановление после мягкого удаления.
     */
    public function restored(User $user): void
    {
        $this->auditService->log($user, $user->getOriginal(), $user->getAttributes(), $this->actorId());
    }

    /**
     * Логировать жёсткое удаление.
     * before = данные до удаления, after = {} (запись уничтожена).
     */
    public function forceDeleted(User $user): void
    {
        $this->auditService->log($user, $user->getAttributes(), [], $this->actorId());
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
