<?php
return [
    'settings' => [
        // comment this line when deploy to production environment
        'displayErrorDetails' => true,
        // View settings
        'view' => [
            'template_path' => __DIR__ . '/../app/templates',
            'twig' => [
                'cache' => __DIR__ . '/../cache/twig',
                'debug' => true,
                'auto_reload' => true,
            ],
        ],

        // doctrine settings
        'doctrine' => [
            'meta' => [
                'entity_path' => [
                    __DIR__ . '/src/models'
                ],
                'auto_generate_proxies' => true,
                'proxy_dir' =>  __DIR__.'/../cache/proxies',
                'cache' => null,
            ],
            'connection' => [
              'driver' => 'pdo_sqlite',
              'path' => __DIR__.'/../sql/blog.sqlite'
            ]
        ],

        // monolog settings
        'logger' => [
            'name' => 'app',
            'path' => __DIR__ . '/../log/app.log',
        ],

		// JWT
		'jwt' => [
			'path' => ['/member'],
            'ignore' => ["/member/login"],
            'secret' => 'supersecretkeyyoushouldnotcommittogithub',
            'validity' => 3600,
            'refresh' => 1800, /* Refresh the jwt/cookie each */
            'relaxed' => ['127.0.0.1', 'localhost', 'slim3.dev.e-dune.info'], /* Url without HTTPS check */
            'algorithm' => 'HS256'
        ]
    ],
];