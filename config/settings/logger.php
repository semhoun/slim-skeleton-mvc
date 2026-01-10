<?php

declare(strict_types=1);

use App\Services\Settings;
use Monolog\Level;

return [
    'name' => 'app',
    'path' => getenv('DOCKER_MODE', true) === 'true' ?
            'php://stdout'
            : Settings::getAppRoot() . '/var/log/app.log',
    'level' => getenv('DEBUG_MODE', true) === 'true' ? Level::Debug : Level::Info,
];
