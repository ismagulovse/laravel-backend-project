<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Бросается, когда деплой уже выполняется и блокировка занята.
 * Соответствует HTTP-статусу 409 Conflict.
 */
class DeploymentInProgressException extends RuntimeException
{
    public function __construct(string $message = 'Deployment already in progress')
    {
        parent::__construct($message);
    }
}
