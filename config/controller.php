<?php
// -----------------------------------------------------------------------------
// Controller factories
// -----------------------------------------------------------------------------

$container = $app->getContainer();


$container['App\Controller\ErrorController'] = function ($c) {
    return new App\Controller\ErrorController($c);
};
$container['App\Controller\HomeController'] = function ($c) {
    return new App\Controller\HomeController($c);
};
$container['App\Controller\AuthController'] = function ($c) {
    return new App\Controller\AuthController($c);
};
