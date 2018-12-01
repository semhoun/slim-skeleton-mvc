<?php
// Routes

$app->get('/', 'App\Controller\HomeController:index');
$app->get('/login', 'App\Controller\HomeController:login');

$app->get('/post/{id}', 'App\Controller\HomeController:viewPost');
$app->get('/member/post/{id}', 'App\Controller\HomeController:viewPost');


$app->post('/member/login', 'App\Controller\AuthController:login');
$app->get('/member/logout', 'App\Controller\AuthController:logout');
