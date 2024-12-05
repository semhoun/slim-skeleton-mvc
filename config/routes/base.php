<?php

declare(strict_types=1);

use Slim\App;

return static function (
    App $app,
): void {
    $app->get('/', [\App\Controller\HomeController::class, 'index'])->setName('home');
    $app->get('/api_info', [\App\Controller\HomeController::class, 'apiInfo'])->setName('apiInfo');
    $app->get('/error', [\App\Controller\HomeController::class, 'error'])->setName('error');
};
