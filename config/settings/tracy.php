<?php

declare(strict_types=1);

use App\Services\Settings;

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
        'XDebugHelperIDEKey' => 'PHPSTORM',
        'ConsoleEnable' => 0,
        'ConsoleNoLogin' => 0,
        'ConsoleAccounts' => [
            'dev' => '34c6fceca75e456f25e7e99531e2425c6c1de443', // = sha1('dev')
        ],
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
            'Doctrine' => \Doctrine\ORM\Configuration::class, // must be a configuration DBAL or ORM
            'Twig' => \Twig\Profiler\Profile::class,
        ],
    ],
];
