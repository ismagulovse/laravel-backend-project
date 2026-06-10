<?php

declare(strict_types=1);

namespace App\Services;

interface DeploymentServiceInterface
{
    public function deploy(?string $clientIp = null): array;
}
