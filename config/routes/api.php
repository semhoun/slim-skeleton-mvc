<?php

declare(strict_types=1);

use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;

return static function (
    App $app,
): void {
    $app->group('/api', static function (Group $group): void {
        $group->get('/post', [\App\Controller\Api\PostController::class, 'getAll'])->setName('apiPostGetAll');
        $group->get('/post/{id}', [\App\Controller\Api\PostController::class, 'get']);
        $group->post('/post', [\App\Controller\Api\PostController::class, 'add']);
        $group->delete('/post/{id}', [\App\Controller\Api\PostController::class, 'delete']);
    });
};
