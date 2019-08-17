<?php
declare(strict_types=1);

use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;

return function (ContainerBuilder $containerBuilder) {
	// Renderer factories
	 $containerBuilder->addDefinitions([
        App\Controller\ErrorRenderer::class => function (ContainerInterface $c) {
            return new App\Controller\ErrorRenderer($c);
        }
    ]);
	
    // Controller factories
    $containerBuilder->addDefinitions([
        App\Controller\HomeController::class => function (ContainerInterface $c) {
            return new App\Controller\HomeController($c);
        },
        App\Controller\AuthController::class => function (ContainerInterface $c) {
            return new App\Controller\AuthController($c);
        },
    ]);
};

