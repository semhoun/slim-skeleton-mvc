<?php

declare(strict_types=1);

use App\Middleware\BaseUrlMiddleware;
use App\Renderer\HtmlErrorRenderer;
use App\Renderer\JsonErrorRenderer;
use App\Services\Settings;
use Odan\Session\Middleware\SessionStartMiddleware;
use RKA\Middleware\ProxyDetection;
use Slim\App;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;

return static function (App $app): void {
    $container = $app->getContainer();
    $settings = $container->get(Settings::class);

    $app->add(SessionStartMiddleware::class);
    $app->add(TwigMiddleware::create($app, $container->get(Twig::class)));
    $app->add(BaseUrlMiddleware::class);
    $app->add(new ProxyDetection());

    // Add error handling middleware.
    if ($settings->get('debug')) {
        $app->add(new SlimTracy\Middlewares\TracyMiddleware($app, $settings->get('tracy')));
        $errorMiddleware = $app->addErrorMiddleware(true, true, true);
    } else {
        $errorMiddleware = $app->addErrorMiddleware(false, true, true);
    }

    $errorHandler = $errorMiddleware->getDefaultErrorHandler();
    $errorHandler->registerErrorRenderer('text/html', HtmlErrorRenderer::class);
    $errorHandler->registerErrorRenderer('application/json', JsonErrorRenderer::class);
    $errorHandler->setDefaultErrorRenderer('application/json', JsonErrorRenderer::class);
};
