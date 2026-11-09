<?php

declare(strict_types=1);

use Slim\App;

return static function (
    App $app,
): void {
    $app->get('/blog/{id:[0-9]+}', [\App\Controller\BlogController::class, 'view'])->setName('blog');
};
