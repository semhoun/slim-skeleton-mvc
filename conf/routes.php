<?php
declare(strict_types=1);

use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;

return function (App $app) {
    $container = $app->getContainer();
    $settings = $container->get('settings');

    $app->get('/', 'App\Controller\HomeController:index')->setName('home');
    $app->get('/error', 'App\Controller\HomeController:error')->setName('error');

    $app->get('/post/{id}', 'App\Controller\HomeController:viewPost')->setName('post');

    $app->group('/member', function (Group $group) {
        $group->map(['GET', 'POST'],'/login', 'App\Controller\AuthController:login')->setName('login');
        $group->get('/logout', 'App\Controller\AuthController:logout')->setName('logout');

    });

    if ($settings['debug'] == true && $settings['tracy']['enableConsoleRoute'] == true) {
        $app->post('/console', 'RunTracy\Controllers\RunTracyConsole:index');
    }
};
