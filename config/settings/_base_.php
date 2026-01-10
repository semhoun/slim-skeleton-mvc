<?php

declare(strict_types=1);

use App\Services\Settings;

return [
    // version, currently juste php version
    'version' => '4.2.0',
    // Is debug moderm
    'debug' => env('DEBUG_MODE', true),
    // API Base path
    'api_base_path' => '/api',
    // 'Temprorary directory
    'temporary_path' => Settings::getAppRoot() . '/var/tmp',
    // Route cache
    'cache_dir' => Settings::getAppRoot() . '/var/cache',
];
