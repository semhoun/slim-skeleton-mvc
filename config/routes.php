<?php

declare(strict_types=1);

use App\Controller;
use App\Services\Settings;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;

return static function (App $app): void {
    $container = $app->getContainer();
    $settings = $container->get(Settings::class);

    $app->get('/health', [Controller\HealthController::class, 'health'])->setName('health');
    $settings = $app->getContainer()->get(Settings::class);
    if ($settings->get('debug') && $settings->get('tracy.configs.ConsoleEnable')) {
        $app->post('/console', 'SlimTracy\Controllers\SlimTracyConsole:index');
    }

    // Activating all routes
    foreach (glob(Settings::getAppRoot() . '/config/routes/*.php') as $file) {
        $route = require $file;
        $route($app);
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
