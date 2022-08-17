#!/usr/bin/env php
<?php
use DI\ContainerBuilder;
use Doctrine\ORM\Tools\Console\ConsoleRunner;
use Symfony\Component\Console\Application;
use Doctrine\ORM\Tools\Console\EntityManagerProvider\SingleManagerProvider;

// Set the absolute path to the root directory.
$rootPath = realpath(__DIR__ . '/..');

require $rootPath . '/vendor/autoload.php';

// Instantiate PHP-DI ContainerBuilder
$containerBuilder = new ContainerBuilder();

// Set up settings
$settings = require $rootPath . '/conf/settings.php';
$settings($containerBuilder);

// Set up dependencies
$dependencies = require $rootPath . '/conf/dependencies.php';
$dependencies($containerBuilder);

// Build PHP-DI Container instance
$container = $containerBuilder->build();


$commands = [
   new \App\Command\InitDB($container)
];

ConsoleRunner::run(
    new SingleManagerProvider($container->get('em')),
    $commands
);
