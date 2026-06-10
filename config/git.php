<?php

declare(strict_types=1);

return [

    'webhook_secret' => env('GIT_WEBHOOK_SECRET'),
    'default_branch' => env('GIT_DEFAULT_BRANCH', 'main'),
    'repository_path' => env('GIT_REPOSITORY_PATH', base_path()),
    'lock_timeout' => (int) env('GIT_DEPLOY_LOCK_TIMEOUT', 300),

];
