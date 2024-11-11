<?php

declare(strict_types=1);

use App\Middleware\BaseUrlMiddleware;
use App\Middleware\SessionMiddleware;
use App\Services\Settings;
use Slim\App;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use Tracy\Debugger;

return static function (App $app): void {
    $container = $app->getContainer();
    $settings = $container->get(Settings::class);

    $app->add(TwigMiddleware::create($app, $container->get(Twig::class)));

    $app->add($container->get(SessionMiddleware::class));
    $app->add($container->get(BaseUrlMiddleware::class));

    $app->add(new \RKA\Middleware\ProxyDetection());

    // Add error handling middleware.
    if ($settings->get('debug')) {
        $app->add(new SlimTracy\Middlewares\TracyMiddleware($app, $settings->get('tracy')));
        Debugger::enable(Debugger::Development);
    }
    $errorMiddleware = $app->addErrorMiddleware($settings->get('debug'), true, true);
    $errorHandler = $errorMiddleware->getDefaultErrorHandler();
    $errorHandler->registerErrorRenderer('text/html', \App\Renderer\HtmlErrorRenderer::class);
    $errorHandler->registerErrorRenderer('application/json', \App\Renderer\JsonErrorRenderer::class);
    $errorHandler->setDefaultErrorRenderer('application/json', \App\Renderer\JsonErrorRenderer::class);
};
