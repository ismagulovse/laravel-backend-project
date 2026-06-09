<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Бросается при ошибке в процессе деплоя: отсутствие git-репозитория,
 * неуспешная git-команда и т. п. Соответствует HTTP-статусу 500.
 */
class DeploymentFailedException extends RuntimeException
{
}
