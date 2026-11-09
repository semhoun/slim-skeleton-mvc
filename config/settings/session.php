<?php

declare(strict_types=1);

return [
    'name' => 'slim_session',
    'lifetime' => 7200,
    'save_path' => null,
    'domain' => null,
    'secure' => env('SESSION_COOKIE_SECURE', env('DEBUG_MODE', false) !== true) === true,
    'httponly' => true,
    'cookie_samesite' => 'Lax',
    'use_strict_mode' => 1,
    'use_only_cookies' => 1,
    'cache_limiter' => 'nocache',
];
