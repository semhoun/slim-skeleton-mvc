<?php
declare(strict_types=1);

use DI\ContainerBuilder;
use Monolog\Logger;

return function (ContainerBuilder $containerBuilder) {
    $rootPath = realpath(__DIR__ . '/..');

    // Global Settings Object
    $containerBuilder->addDefinitions([
        'settings' => [
            // Base path
            'base_path' => '',
        
            // Is debug mode
            'debug' => (getenv('APPLICATION_ENV') != 'production'),

            // 'Temprorary directory
            'temporary_path' => $rootPath . '/var/tmp',

            // Route cache
            'route_cache' =>$rootPath . '/var/cache/routes',

            // View settings
            'view' => [
                'template_path' =>$rootPath . '/tmpl',
                'twig' => [
                    'cache' =>$rootPath . '/var/cache/twig',
                    'debug' => (getenv('APPLICATION_ENV') != 'production'),
                    'auto_reload' => true,
                ],
            ],

            // doctrine settings
            'doctrine' => [
                'meta' => [
                    'entity_path' => [ $rootPath . '/src/Entity' ],
                    'auto_generate_proxies' => true,
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
                'path' =>  getenv('docker') ? 'php://stdout' : $rootPath . '/var/log/app.log',
                'level' => (getenv('APPLICATION_ENV') != 'production') ? Logger::DEBUG : Logger::INFO,
            ]
        ],
    ]);

    if (getenv('APPLICATION_ENV') == 'production') { // Should be set to true in production
        $containerBuilder->enableCompilation($rootPath . '/var/cache');
    }
};
