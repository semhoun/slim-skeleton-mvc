<?php

declare(strict_types=1);

use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;

return static function (
    App $app,
): void {
    $app->group('/member', static function (Group $group): void {
        $group->map(['GET', 'POST'], '/login', [\App\Controller\AuthController::class, 'login'])->setName('login');
        $group->get('/logout', [\App\Controller\AuthController::class, 'logout'])->setName('logout');
    });
};
