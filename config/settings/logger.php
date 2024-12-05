<?php

declare(strict_types=1);

use App\Services\Settings;
use Monolog\Level;

$debug = getenv('DEBUG_MODE', true) === 'true';
$docker = getenv('DOCKER_MODE', true) === 'true';

return [
    'name' => 'app',
    'path' => $docker ?
            'php://stdout'
            : Settings::getAppRoot() . '/var/log/app.log',
    'level' => $debug ? Level::Debug : Level::Info,
];
