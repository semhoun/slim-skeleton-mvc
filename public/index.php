<?php
declare(strict_types=1);

use DI\ContainerBuilder;
use Slim\Factory\AppFactory;
use Slim\Factory\ServerRequestCreatorFactory;

// Set the absolute path to the root directory.
$rootPath = realpath(__DIR__ . '/..');

// Include the composer autoloader.
include_once($rootPath . '/vendor/autoload.php');

// Needed by Flash and SessionMiddleware
session_start();

// Instantiate PHP-DI ContainerBuilder
$containerBuilder = new ContainerBuilder();

// Set up settings
$settings = require $rootPath . '/app/settings.php';
$settings($containerBuilder);

// Set up dependencies
$dependencies = require $rootPath . '/app/dependencies.php';
$dependencies($containerBuilder);

// Set up factories
$factories = require $rootPath . '/app/factories.php';
$factories($containerBuilder);

// Build PHP-DI Container instance
$container = $containerBuilder->build();

$settings = $container->get('settings');

// Instantiate the app
AppFactory::setContainer($container);
$app = AppFactory::create();
$callableResolver = $app->getCallableResolver();

// Register middleware
$middleware = require $rootPath . '/app/middleware.php';
$middleware($app);

// Register routes
$routes = require $rootPath . '/app/routes.php';
$routes($app);

// Set the cache file for the routes. Note that you have to delete this file
// whenever you change the routes.
if (!$settings['debug']) {
    $app->getRouteCollector()->setCacheFile($settings['route_cache']);
}

// Add the routing middleware.
$app->addRoutingMiddleware();

// Add error handling middleware.
$errorMiddleware = $app->addErrorMiddleware($settings['debug'], !$settings['debug'], false);
$errorHandler = $errorMiddleware->getDefaultErrorHandler();
$errorHandler->registerErrorRenderer('text/html', App\Renderer\HtmlErrorRenderer::class);

// Run the app
$app->run();
