<?php

declare(strict_types=1);

use DI\Container;
use Slim\App;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\TwigMiddleware;

return function (App $app) {
    $container = $app->getContainer();
    $settings = $container->get('settings');

    $app->add($container->get('session'));

    $app->add(TwigMiddleware::createFromContainer($app));

    $app->add(new \App\Middleware\BaseUrlMiddleware($app->getBasePath()));

    $app->add(new \RKA\Middleware\ProxyDetection());

    if ($settings['debug'] == true) {
        $app->add(new RunTracy\Middlewares\TracyMiddleware($app));
    }
};
