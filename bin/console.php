#!/usr/bin/env php
<?php
use DI\ContainerBuilder;
use Symfony\Component\Console\Application;


// Set the absolute path to the root directory.
$rootPath = realpath(__DIR__ . '/..');

require $rootPath . '/vendor/autoload.php';

// Instantiate PHP-DI ContainerBuilder
$containerBuilder = new ContainerBuilder();

// Set up settings
$settings = require $rootPath . '/conf/settings.php';
$settings($containerBuilder);

// Build PHP-DI Container instance
$container = $containerBuilder->build();

$application = new Application();
$application->add(new App\Command\InitDB($container));

$application->run();
