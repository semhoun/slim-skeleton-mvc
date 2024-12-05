<?php

declare(strict_types=1);

use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;

return static function (
    App $app,
): void {
    $app->group('/admin', static function (Group $group): void {
        $group->get('', [\App\Controller\AdminController::class, 'view'])->setName('admin');
        $group->get('/database', [\App\Controller\AdminController::class, 'databasesInfo'])->setName('adminDatabasesInfo');
        $group->get('/database/{id}', [\App\Controller\AdminController::class, 'databaseExport'])->setName('adminDatabaseExport');
    });
};
