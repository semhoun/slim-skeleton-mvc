<?php

declare(strict_types=1);

use App\Services\Settings;
use Monolog\Logger;

$debug = getenv('DEBUG_MODE', true) === 'true';
$docker = getenv('DOCKER_MODE', true) === 'true';

return [
    // Is debug moderm
    'debug' => $debug,
    // 'Temprorary directory
    'temporary_path' => Settings::getAppRoot() . '/var/tmp',
    // Route cache
    'cache_dir' => Settings::getAppRoot() . '/var/cache',
    // doctrine settings
    'doctrine' => [
        'entity_path' => [Settings::getAppRoot() . '/src/Entity'],
        'connection' => [
            'driver' => 'pdo_sqlite',
            'path' => Settings::getAppRoot() . '/var/blog.sqlite',
        ],
        'migrations' => [
            'table_storage' => [
                'table_name' => 'db_version',
                'version_column_name' => 'version',
                'version_column_length' => 1024,
                'executed_at_column_name' => 'executed_at',
                'execution_time_column_name' => 'execution_time',
            ],
            'migrations_paths' => [
                'Skeleton' => Settings::getAppRoot() . '/migrations',
            ],
            'all_or_nothing' => true,
            'transactional' => true,
            'check_database_platform' => true,
            'organize_migrations' => 'none',
            'connection' => null,
            'em' => null,
            'custom_template' => Settings::getAppRoot() . '/migrations/doctrine_migrations_class.php.tpl',
        ],
    ],
    // View settings
    'view' => [
        'template_path' => Settings::getAppRoot() .  '/tmpl',
        'twig' => [
            'cache' => Settings::getAppRoot() . '/var/cache/twig',
            'debug' => $debug,
            'auto_reload' => true,
        ],
    ],
    // monolog settings
    'logger' => [
        'name' => 'app',
        'path' => $docker ? 'php://stdout' : Settings::getAppRoot() . '/var/log/app.log',
        'level' => $debug ? Logger::DEBUG : Logger::INFO,
    ],
    // tracy
    'tracy' => [
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
            // XDebugger IDE key
            'XDebugHelperIDEKey' => 'PHPSTORM',
            // Activate the console
            'ConsoleEnable' => 1,
            // Disable login (don't ask for credentials, be careful) values( 1 || 0 )
            'ConsoleNoLogin' => 0,
            // Multi-user credentials values( ['user1' => 'password1', 'user2' => 'password2'] )
            'ConsoleAccounts' => [
                'dev' => '34c6fceca75e456f25e7e99531e2425c6c1de443', // = sha1('dev')
            ],
            // Password hash algorithm (password must be hashed) values('md5', 'sha256' ...)
            'ConsoleHashAlgorithm' => 'sha1',
            // Home directory (multi-user mode supported) values ( var || array )
            // '' || '/tmp' || ['user1' => '/home/user1', 'user2' => '/home/user2']
            'ConsoleHomeDirectory' => Settings::getAppRoot(),
            // terminal.js full URI
            'ConsoleTerminalJs' => 'https://cdnjs.cloudflare.com/ajax/libs/jquery.terminal/2.42.2/js/jquery.terminal.min.js',
            // terminal.css full URI
            'ConsoleTerminalCss' => 'https://cdnjs.cloudflare.com/ajax/libs/jquery.terminal/2.42.2/css/jquery.terminal.min.css',
            'ConsoleFromEncoding' => 'UTF-8', // or false
            'ProfilerPanel' => [
                // Memory usage 'primaryValue' set as Profiler::enable() or Profiler::enable(1)
                //                    'primaryValue' =>                   'effective',    // or 'absolute'
                'show' => [
                    'memoryUsageChart' => 1, // or false
                    'shortProfiles' => true, // or false
                    'timeLines' => true, // or false
                ],
            ],
            'Container' => [
                // Container entry name
                'Doctrine' => \Doctrine\ORM\Configuration::class, // must be a configuration DBAL or ORM
                'Twig' => \Twig\Profiler\Profile::class,
            ],
        ],
    ],
];
