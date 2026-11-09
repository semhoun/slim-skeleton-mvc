<?php

declare(strict_types=1);

use App\Services\Settings;
use Doctrine\ORM\Configuration;
use Twig\Profiler\Profile;

// terminal.js full URI
$consoleTerminalJs = 'https://cdnjs.cloudflare.com';
$consoleTerminalJs .= '/ajax/libs/jquery.terminal/2.42.2/js/';
$consoleTerminalJs .= 'jquery.terminal.min.js';

// terminal.css full URI
$consoleTerminalCss = 'https://cdnjs.cloudflare.com';
$consoleTerminalCss .= '/ajax/libs/jquery.terminal/';
$consoleTerminalCss .= 'jquery.terminal.min.css';

return [
    'showPhpInfoPanel' => 0,
    'showSlimRouterPanel' => 0,
    'showSlimEnvironmentPanel' => 0,
    'showSlimRequestPanel' => 1,
    'showSlimResponsePanel' => 1,
    'showSlimContainer' => 0,
    'showEloquentORMPanel' => 0,
    'showTwigPanel' => 1,
    'showDoctrinePanel' => 1,
    'showProfilerPanel' => 0,
    'showVendorVersionsPanel' => 0,
    'showXDebugHelper' => 0,
    'showIncludedFiles' => 0,
    'showConsolePanel' => 0,
    'configs' => [
        'ShowBar' => env('DISABLE_TRACY_BAR', false) !== true ? 1 : 0,
        'XDebugHelperIDEKey' => 'PHPSTORM',
        'ConsoleEnable' => 0,
        'ConsoleNoLogin' => 0,
        'ConsoleAccounts' => [],
        'ConsoleHashAlgorithm' => 'sha1',
        'ConsoleHomeDirectory' => Settings::getAppRoot(),
        // terminal.js full URI
        'ConsoleTerminalJs' => $consoleTerminalJs,
        // terminal.css full URI
        'ConsoleTerminalCss' => $consoleTerminalCss,
        'ConsoleFromEncoding' => 'UTF-8',
        'ProfilerPanel' => [
            'show' => [
                'memoryUsageChart' => 1,
                'shortProfiles' => true,
                'timeLines' => true,
            ],
        ],
        'Container' => [
            // Container entry name
            'Doctrine' => Configuration::class, // must be a configuration DBAL or ORM
            'Twig' => Profile::class,
        ],
        'Debugger' => [
            // Tracy accepts true for production and false for development.
            // Avoid loading this optional development dependency in production.
            'mode' => env('DEBUG_MODE', false) !== true,
            'logDirectory' => null,
            'email' => null,
        ],
        'AjaxProfiler' => [
            // Shared, unauthenticated profiles: protected development networks only.
            'enabled' => env('TRACY_API_PROFILER', false) === true,
            'origins' => [],
            'path' => '/_tracy/api',
            'storageDirectory' => Settings::getAppRoot() . '/var/tmp/api-profiles',
            'ttl' => 600,
            'maxProfiles' => 100,
            'maxProfileBytes' => 262144,
            'maxTotalBytes' => 10485760,
        ],
    ],
];
