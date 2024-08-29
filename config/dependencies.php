<?php

declare(strict_types=1);

use App\Services\Settings;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Slim\Views\Twig;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;

return [
    // Doctrine Dbal.
    \Doctrine\DBAL\Connection::class => static fn (Settings $settings): \Doctrine\DBAL\Connection => \Doctrine\DBAL\DriverManager::getConnection($settings->get('doctrine.connection')),
    // Doctrine EntityManager.
    EntityManager::class => static function (Settings $settings, \Doctrine\DBAL\Connection $connection): EntityManager {
        if ($settings->get('debug')) {
            $queryCache = new ArrayAdapter();
            $metadataCache = new ArrayAdapter();
        } else {
            $queryCache = new PhpFilesAdapter('queries', 0, $settings->get('cache_dir'));
            $metadataCache = new PhpFilesAdapter('metadata', 0, $settings->get('cache_dir'));
        }

        $config = new \Doctrine\ORM\Configuration();
        $config->setMetadataCache($metadataCache);
        $driverImpl = new \Doctrine\ORM\Mapping\Driver\AttributeDriver($settings->get('doctrine.entity_path'), true);
        $config->setMetadataDriverImpl($driverImpl);
        $config->setQueryCache($queryCache);
        $config->setProxyDir($settings->get('cache_dir') . '/proxy');
        $config->setProxyNamespace('App\Proxies');

        if ($settings->get('debug')) {
            $config->setAutoGenerateProxyClasses(true);
        } else {
            $config->setAutoGenerateProxyClasses(false);
        }
        return new EntityManager($connection, $config);
    },
    EntityManagerInterface::class => DI\get(EntityManager::class),
    // Settings.
    Settings::class => DI\factory([Settings::class, 'load']),
    Logger::class => static function (Settings $settings): Logger {
        $logger = new Logger($settings->get('logger.name'));
        $processor = new UidProcessor();
        $logger->pushProcessor($processor);

        $handler = new StreamHandler($settings->get('logger.path'), $settings->get('logger.level'));
        $logger->pushHandler($handler);

        return $logger;
    },
    Twig::class => static fn (Settings $settings): Twig => Twig ::create($settings->get('view.template_path'), $settings->get('view.twig')),
];
