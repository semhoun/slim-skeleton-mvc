<?php

declare(strict_types=1);

use Slim\App;

return static function (
    App $app,
): void {
    $app->get('/blog/{id}', [\App\Controller\BlogController::class, 'view'])->setName('blog');
};
