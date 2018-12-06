<?php

$container = $app->getContainer();

// -----------------------------------------------------------------------------
// JWT Authentication
// -----------------------------------------------------------------------------
$container['JwtAuthentication'] = function ($c) {
    $settings = $c->get('settings');
    return new Tuupola\Middleware\JwtAuthentication([
        'path' => $settings['jwt']['path'],
        'ignore' => $settings['jwt']['ignore'],
        'secret' => $settings['jwt']['secret'],
        'logger' => $c['logger'],
        'algorithm' => $settings['jwt']['algorithm'],
        'relaxed' => $settings['jwt']['relaxed'],
        'attribute' => false,
        'error' => function ($response, $arguments) use ($c) {
            return $c->get('App\Controller\ErrorController')->E401(null, $response, $arguments);
        },
        'before' => function ($request, $arguments) use ($c) {
            $c['token']->populate($arguments['decoded']);
        },
        'after' => function ($response, $arguments) use ($c) {
            return $c->get('auth')->precheck($response, $arguments);
        }
    ]);
};
$container["token"] = function ($container) {
    return new \App\Lib\Token;
};
$container['auth'] = function($c) {
    return new \App\Lib\JwtAuth($c);
};

$app->add('JwtAuthentication');

