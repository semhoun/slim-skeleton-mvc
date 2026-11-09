<?php

declare(strict_types=1);

use App\Services\Settings;

return [
    // version, currently juste php version
    'version' => '4.2.0',
    // Enable debugging explicitly on development machines only.
    'debug' => env('DEBUG_MODE', false) === true,
    'trusted_proxies' => array_values(array_filter(array_map(
        trim(...),
        explode(',', (string) env('TRUSTED_PROXIES', ''))
    ))),
    // API Base path
    'api_base_path' => '/api',
    // 'Temprorary directory
    'temporary_path' => Settings::getAppRoot() . '/var/tmp',
    // Route cache
    'cache_dir' => Settings::getAppRoot() . '/var/cache',
];
