<?php

declare(strict_types=1);

use App\Controller;
use App\Services\Settings;
use Slim\App;

return static function (App $app): void {
    $container = $app->getContainer();
    $settings = $container->get(Settings::class);

    $app->get('/health', [Controller\HealthController::class, 'health'])->setName('health');
    if ($settings->get('debug')) {
        SlimTracy\Profiling\Routes::register(
            $app,
            SlimTracy\Profiling\Configuration::fromSettings($settings->get('tracy'))
        );
        // Vendor handlers expect Slim's positional arguments, not PHP-DI parameter injection.
        foreach ($app->getRouteCollector()->getRoutes() as $route) {
            if ($route->getCallable() instanceof SlimTracy\Profiling\Routes) {
                $route->setInvocationStrategy(new Slim\Handlers\Strategies\RequestResponse());
            }
        }
    }

    if ($settings->get('debug') && $settings->get('tracy.configs.ConsoleEnable')) {
        $app->post('/console', 'SlimTracy\Controllers\SlimTracyConsole:index');
    }

    // Activating all routes
    foreach (glob(Settings::getAppRoot() . '/config/routes/*.php') as $file) {
        $route = require $file;
        $route($app);
    }
};
