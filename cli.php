<?php
use DI\ContainerBuilder;
use Symfony\Component\Console\Application;

require __DIR__.'/vendor/autoload.php';

// Instantiate PHP-DI ContainerBuilder
$containerBuilder = new ContainerBuilder();

// Set up settings
$settings = require __DIR__ . '/conf/settings.php';
$settings($containerBuilder);

// Build PHP-DI Container instance
$container = $containerBuilder->build();

$application = new Application();
$application->add(new App\Command\InitDB($container));

$application->run();
