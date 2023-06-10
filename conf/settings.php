<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use Monolog\Logger;
use Tracy\Debugger;

return function (ContainerBuilder $containerBuilder) {
    $rootPath = realpath(__DIR__ . '/..');
    $debug = true;
    $docker = true;

    // Global Settings Object
    $containerBuilder->addDefinitions([
        'settings' => [
            // Base path
            'base_path' => '',

            // Is debug moderm
            'debug' => $debug,

            // 'Temprorary directory
            'temporary_path' => $rootPath . '/var/tmp',

            // Route cache
            'route_cache' => $rootPath . '/var/cache/routes',

            // View settings
            'view' => [
                'template_path' => $rootPath . '/tmpl',
                'twig' => [
                    'cache' => $rootPath . '/var/cache/twig',
                    'debug' => $debug,
                    'auto_reload' => true,
                ],
            ],

            // doctrine settings
            'doctrine' => [
                'dev_mode' => $debug,
                'meta' => [
                    'entity_path' => [$rootPath . '/src/Entity'],
                    'proxy_dir' => $rootPath . '/var/cache/proxies',
                    'cache' => null,
                ],
                'connection' => [
                    'driver' => 'pdo_sqlite',
                    'path' => $rootPath . '/var/blog.sqlite'
                ]
            ],

            // monolog settings
            'logger' => [
                'name' => 'app',
                'path' =>  $docker ? 'php://stdout' : $rootPath . '/var/log/app.log',
                'level' => $debug ? Logger::DEBUG : Logger::INFO,
            ],

            'tracy' => [
                'enableConsoleRoute' => $debug,
                'showPhpInfoPanel' => 0,
                'showSlimRouterPanel' => 0,
                'showSlimEnvironmentPanel' => 0,
                'showSlimRequestPanel' => 1,
                'showSlimResponsePanel' => 1,
                'showSlimContainer' => 0,
                'showEloquentORMPanel' => 0,
                'showTwigPanel' => 0,
                'showIdiormPanel' => 0, // > 0 mean you enable logging
                // but show or not panel you decide in browser in panel selector
                'showDoctrinePanel' => 'entity_manager', // here also enable logging and you must enter your Doctrine container name
                // and also as above show or not panel you decide in browser in panel selector
                'showProfilerPanel' => 0,

                'showVendorVersionsPanel' => 0,
                'showXDebugHelper' => 0,
                'showIncludedFiles' => 0,
                'showConsolePanel' => 0,
                'configs' => [
                    // XDebugger IDE key
                    'XDebugHelperIDEKey' => 'PHPSTORM',
                    // Disable login (don't ask for credentials, be careful) values( 1 || 0 )
                    'ConsoleNoLogin' => 0,
                    // Multi-user credentials values( ['user1' => 'password1', 'user2' => 'password2'] )
                    'ConsoleAccounts' => [
                        'dev' => '34c6fceca75e456f25e7e99531e2425c6c1de443' // = sha1('dev')
                    ],
                    // Password hash algorithm (password must be hashed) values('md5', 'sha256' ...)
                    'ConsoleHashAlgorithm' => 'sha1',
                    // Home directory (multi-user mode supported) values ( var || array )
                    // '' || '/tmp' || ['user1' => '/home/user1', 'user2' => '/home/user2']
                    'ConsoleHomeDirectory' => $rootPath,
                    // terminal.js full URI
                    'ConsoleTerminalJs' => '/js/jquery.terminal.min.js',
                    // terminal.css full URI
                    'ConsoleTerminalCss' => '/css/jquery.terminal.min.css',
                    'ConsoleFromEncoding' => 'UTF-8', // or false
                    'ProfilerPanel' => [
                        // Memory usage 'primaryValue' set as Profiler::enable() or Profiler::enable(1)
                        //                    'primaryValue' =>                   'effective',    // or 'absolute'
                        'show' => [
                            'memoryUsageChart' => 1, // or false
                            'shortProfiles' => true, // or false
                            'timeLines' => true // or false
                        ]
                    ]
                ]
            ],
        ]
    ]);

    if ($debug == false) {
        $containerBuilder->enableCompilation($rootPath . '/var/cache');
    } else {
        Debugger::enable(Debugger::DEVELOPMENT, $rootPath . '/var/log');
    }
};
