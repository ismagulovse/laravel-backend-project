<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;


class DeploymentInProgressException extends RuntimeException
{
    public function __construct(string $message = 'Deployment already in progress')
    {
        parent::__construct($message);
    }
}
