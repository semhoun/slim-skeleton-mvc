<?php
declare(strict_types=1);

use DI\ContainerBuilder;
use Monolog\Logger;

return function (ContainerBuilder $containerBuilder) {
    // Global Settings Object
    $containerBuilder->addDefinitions([
		'settings' => [
            // Is debug mode
            'debug' => (getenv('APPLICATION_ENV') != 'production'),

            // 'Temprorary directory
            'temporary_path' =>  __DIR__ . '/../var/tmp',

            // Route cache
            'route_cache' => __DIR__ . '/../var/routes.cache',

			// View settings
			'view' => [
				'template_path' => __DIR__ . '/../templates',
				'twig' => [
					'cache' => __DIR__ . '/../var/cache/twig',
					'debug' => (getenv('APPLICATION_ENV') != 'production'),
					'auto_reload' => true,
				],
			],

			// doctrine settings
			'doctrine' => [
				'meta' => [
					'entity_path' => [
						__DIR__ . '/src/Entity'
					],
					'auto_generate_proxies' => true,
					'proxy_dir' =>  __DIR__.'/../var/cache/proxies',
					'cache' => null,
				],
				'connection' => [
                    'driver' => 'pdo_sqlite',
                    'path' => __DIR__.'/../var/blog.sqlite'
				]
			],

			// monolog settings
			'logger' => [
				'name' => 'app',
				'path' =>  isset($_ENV['docker']) ? 'php://stdout' : __DIR__ . '/../var/log/app.log',
				'level' => (getenv('APPLICATION_ENV') != 'production') ? Logger::DEBUG : Logger::INFO,
			]
		],
	]);

    if (getenv('APPLICATION_ENV') == 'production') { // Should be set to true in production
        $containerBuilder->enableCompilation(__DIR__ . '/../var/cache');
    }
};
