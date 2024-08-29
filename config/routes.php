<?php

declare(strict_types=1);

use App\Services\Settings;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;

return static function (App $app): void {
    $app->get('/', [\App\Controller\HomeController::class, 'index'])->setName('home');
    $app->get('/error', [\App\Controller\HomeController::class, 'error'])->setName('error');

    $app->get('/blog/{id}', [\App\Controller\BlogController::class, 'view'])->setName('blog');

    $app->group('/member', static function (Group $group): void {
        $group->map(['GET', 'POST'], '/login', [\App\Controller\AuthController::class, 'login'])->setName('login');
        $group->get('/logout', [\App\Controller\AuthController::class, 'logout'])->setName('logout');
    });

    $settings = $app->getContainer()->get(Settings::class);
    if ($settings->get('debug') === true && $settings->get('tracy.enableConsoleRoute') === true) {
        $app->post('/console', 'RunTracy\Controllers\RunTracyConsole:index');
    }

    // Not Found
    $app->map(
        ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'],
        '/{routes:.*}',
        static function (Request $request): void {
            throw new Slim\Exception\HttpNotFoundException($request);
        }
    );
};
