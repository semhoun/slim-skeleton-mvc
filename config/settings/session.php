<?php

declare(strict_types=1);

return [
    'name' => '__Host-slim-session',
    'namespace' => 'slim_skeleton',
    'lifetime' => 7200,
    'save_path' => \App\Services\Settings::getAppRoot() . '/var/sessions',
    'origin' => env('SESSION_ORIGIN'),
    'signing_key' => env('SESSION_SIGNING_KEY'),
];
