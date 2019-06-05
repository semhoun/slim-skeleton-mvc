<?php
require __DIR__.'/vendor/autoload.php';

use Symfony\Component\Console\Application;

$settings = require __DIR__ . '/config/settings.php';
$c = new \Slim\Container($settings);

$app = new \Slim\App($c);

require __DIR__ . '/config/dependencies.php';

$application = new Application();
$application->add(new App\Command\InitDB($c));

$application->run();
