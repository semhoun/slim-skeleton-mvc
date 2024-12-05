<?php

declare(strict_types=1);

use App\Services\Settings;

$debug = getenv('DEBUG_MODE', true) === 'true';

return [
    // version, currently juste php version
    'version' => phpversion(),
    // Is debug moderm
    'debug' => $debug,
    // API Base path
    'api_base_path' => '/api',
    // 'Temprorary directory
    'temporary_path' => Settings::getAppRoot() . '/var/tmp',
    // Route cache
    'cache_dir' => Settings::getAppRoot() . '/var/cache',
];
