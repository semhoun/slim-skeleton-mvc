<?php

if (PHP_SAPI == 'cli-server') {
    // To help the built-in PHP dev server, check if the request was actually for
    // something which should probably be served as a static file
    $file = __DIR__ . $_SERVER['REQUEST_URI'];
    if (is_file($file)) {
        return false;
    }
}

require __DIR__ . '/../vendor/autoload.php';

session_start();

// Instantiate the app
$settings = require __DIR__ . '/../config/settings.php';
$c = new \Slim\Container($settings);
$c['notFoundHandler'] = function ($c) {
    return function ($request, $response) use ($c) {
        return $c->get( 'App\Controller\ErrorController')->E404($request, $response, null);
    };
};
$app = new \Slim\App($c);

// Set up dependencies
require __DIR__ . '/../config/dependencies.php';

// Register middleware
require __DIR__ . '/../config/middleware.php';

// Register controller factories
require __DIR__ . '/../config/controller.php';

// Register routes
require __DIR__ . '/../config/routes.php';

// Run!
$app->run();
