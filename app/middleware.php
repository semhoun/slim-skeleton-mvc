<?php
declare(strict_types=1);

use DI\Container;
use Slim\App;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use App\Middleware\SessionMiddleware;

return function(App $app) {
    $container = $app->getContainer();
    $settings = $container->get('settings');

    $app->add($container->get('session'));

    $app->add(
        new TwigMiddleware(
            new Twig(
                $settings['view']['template_path'],
                $settings['view']['twig']
            ),
            $container,
            $app->getRouteCollector()->getRouteParser(),
            $app->getBasePath()
        )
    );
};
