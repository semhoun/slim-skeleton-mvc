<?php

declare(strict_types=1);

use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;

return static function (
    App $app,
): void {
    $basePath = $app->getContainer()->get(\App\Services\Settings::class)->get('api_base_path');
    $app->group($basePath, static function (Group $group): void {
        $group->get('/post', [\App\Controller\Api\PostController::class, 'getAll'])->setName('apiPostGetAll');
        $group->get('/post/{id:[0-9]+}', [\App\Controller\Api\PostController::class, 'get']);
        $group->post('/post', [\App\Controller\Api\PostController::class, 'add'])
            ->add(\App\Middleware\AuthenticationMiddleware::class);
        $group->delete('/post/{id:[0-9]+}', [\App\Controller\Api\PostController::class, 'delete'])
            ->add(\App\Middleware\AuthenticationMiddleware::class);
    });
};
