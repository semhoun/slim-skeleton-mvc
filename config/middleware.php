<?php

declare(strict_types=1);

use App\Middleware\BaseUrlMiddleware;
use App\Middleware\SessionSecurityMiddleware;
use App\Renderer\HtmlErrorRenderer;
use App\Renderer\JsonErrorRenderer;
use App\Services\Settings;
use Monolog\Logger;
use Odan\Session\Middleware\SessionStartMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RKA\Middleware\ProxyDetection;
use Slim\App;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;

return static function (App $app): void {
    $container = $app->getContainer();
    $settings = $container->get(Settings::class);

    $app->add(SessionSecurityMiddleware::class);
    $app->add(SessionStartMiddleware::class);
    $app->add(TwigMiddleware::create($app, $container->get(Twig::class)));
    $app->add(BaseUrlMiddleware::class);

    $trustedProxies = $settings->get('trusted_proxies');
    if ($trustedProxies !== []) {
        $app->add(new ProxyDetection($trustedProxies));
    }

    $app->addBodyParsingMiddleware();
    $app->addRoutingMiddleware();

    // Add error handling middleware.
    if ($settings->get('debug')) {
        $app->add(new SlimTracy\Middlewares\TracyMiddleware($app, $settings->get('tracy')));
        $errorMiddleware = $app->addErrorMiddleware(true, true, true, $container->get(Logger::class));
    } else {
        $errorMiddleware = $app->addErrorMiddleware(false, true, true, $container->get(Logger::class));
    }

    $errorHandler = $errorMiddleware->getDefaultErrorHandler();
    $errorHandler->registerErrorRenderer('text/html', HtmlErrorRenderer::class);
    $errorHandler->registerErrorRenderer('application/json', JsonErrorRenderer::class);
    $errorHandler->setDefaultErrorRenderer('application/json', JsonErrorRenderer::class);

    $apiPath = $app->getBasePath() . $settings->get('api_base_path');
    $errorMiddleware->setDefaultErrorHandler(static function (
        ServerRequestInterface $request,
        Throwable $exception,
        bool $displayErrorDetails,
        bool $logErrors,
        bool $logErrorDetails
    ) use ($errorHandler, $apiPath): ResponseInterface {
        $path = $request->getUri()->getPath();
        $isApi = $path === $apiPath || str_starts_with($path, $apiPath . '/');
        $errorHandler->forceContentType($isApi ? 'application/json' : null);

        return $errorHandler($request, $exception, $displayErrorDetails, $logErrors, $logErrorDetails)
            ->withHeader('Cache-Control', 'no-store')
            ->withHeader('X-Content-Type-Options', 'nosniff');
    });
};
