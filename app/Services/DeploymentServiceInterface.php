<?php

declare(strict_types=1);

namespace App\Services;

interface DeploymentServiceInterface
{
    /**
     * Выполнить деплой: переключиться на нужную ветку, сбросить локальные
     * изменения и подтянуть свежий код. Захватывает блокировку на время работы.
     *
     * @param string|null $clientIp IP инициатора (для логов)
     *
     * @return array<string, mixed> Итог деплоя (статус, ветка, выполненные команды)
     *
     * @throws \App\Exceptions\DeploymentInProgressException Если деплой уже идёт (409)
     * @throws \App\Exceptions\DeploymentFailedException     При ошибке выполнения (500)
     */
    public function deploy(?string $clientIp = null): array;
}
