<?php
declare(strict_types=1);

use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;

return function (App $app) {
    $app->get('/', 'App\Controller\HomeController:index');

    $app->get('/post/{id}', 'App\Controller\HomeController:viewPost');

    $app->group('/member', function (Group $group) {
        $group->map(['GET', 'POST'],'/login', 'App\Controller\AuthController:login');
        $group->get('/logout', 'App\Controller\AuthController:logout');

    });
};
