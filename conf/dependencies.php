<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Psr\Container\ContainerInterface;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\EntityManager;
use Slim\Views\Twig;

return function (ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions([
        'logger' => function (ContainerInterface $container) {
            $settings = $container->get('settings');

            $loggerSettings = $settings['logger'];
            $logger = new Logger($loggerSettings['name']);

            $processor = new UidProcessor();
            $logger->pushProcessor($processor);

            $handler = new StreamHandler($loggerSettings['path'], $loggerSettings['level']);
            $logger->pushHandler($handler);

            return $logger;
        },
        'entity_manager' => function (ContainerInterface $container) {
            $settings = $container->get('settings');
            $config = ORMSetup::createAnnotationMetadataConfiguration(
                $settings['doctrine']['meta']['entity_path'],
                $settings['doctrine']['dev_mode'],
                $settings['doctrine']['meta']['proxy_dir'],
                $settings['doctrine']['meta']['cache'],
                false
            );

            return EntityManager::create($settings['doctrine']['connection'], $config);
        },
        'session' => function (ContainerInterface $container) {
            return new \App\Middleware\SessionMiddleware;
        },
        'flash' => function (ContainerInterface $container) {
            $session = $container->get('session');
            return new \Slim\Flash\Messages($session);
        },
        'twig_profile' => function () {
            return new \Twig\Profiler\Profile();
        },
        'view' => function (ContainerInterface $container) {
            $settings = $container->get('settings');
            return Twig::create($settings['view']['template_path'], $settings['view']['twig']);
        },
    ]);
};
