<?php

declare(strict_types=1);

use App\Services\Settings;
use Monolog\Level;

return [
    'name' => 'app',
    'path' => env('DOCKER_MODE', true) ?
            'php://stdout'
            : Settings::getAppRoot() . '/var/log/app.log',
    'level' => env('DEBUG_MODE', true) ? Level::Debug : Level::Info,
];
