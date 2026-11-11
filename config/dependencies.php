<?php

declare(strict_types=1);

use App\Services\Settings;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Semhoun\SlimSession\Clock\SystemClock;
use Semhoun\SlimSession\Http\HttpConfig;
use Semhoun\SlimSession\Http\SessionMiddleware;
use Semhoun\SlimSession\Jwt\JwtService;
use Semhoun\SlimSession\SessionConfig;
use Semhoun\SlimSession\SessionManager;
use Semhoun\SlimSession\Storage\FileSessionStore;
use Semhoun\SlimSession\Storage\SessionStore;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Views\Twig;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;
use Twig\Extension\DebugExtension;
use Twig\Extension\ProfilerExtension;

return [
    // Doctrine Dbal connection
    Connection::class => static function (Settings $settings, Configuration $configuration): Connection {
        $connectionParams = [
            'driver' => 'pdo_' . $settings->get('database.driver'),
        ];
        if ($settings->get('database.driver') === 'sqlite') {
            $connectionParams['path'] = $settings->get('database.path');
        }

        return DriverManager::getConnection($connectionParams, $configuration);
    },
    // Doctrine Config used by entity manager and Tracy
    Configuration::class => static function (Settings $settings): Doctrine\ORM\Configuration {
        $isDevMode = $settings->get('debug');
        $entityPaths = $settings->get('database.doctrine.entity_path');
        $cacheDir = $settings->get('cache_dir');

        $queryCache = $isDevMode ? new ArrayAdapter() : new PhpFilesAdapter('queries', 0, $cacheDir);
        $metadataCache = $isDevMode ? new ArrayAdapter() : new PhpFilesAdapter('metadata', 0, $cacheDir);

        // Configuration manuelle avec cache explicite
        $configuration = ORMSetup::createAttributeMetadataConfiguration(
            $entityPaths,
            $isDevMode,
            $cacheDir . '/proxy',
            $metadataCache
        );

        $configuration->setQueryCache($queryCache);
        $configuration->setProxyDir($cacheDir . '/proxy');
        $configuration->setProxyNamespace('App\\Proxies');

        $configuration->setAutoGenerateProxyClasses($isDevMode);

        return $configuration;
    },
    // Doctrine EntityManager.
    EntityManager::class => static fn (
        Configuration $configuration,
        Connection $connection
    ): EntityManager => new EntityManager($connection, $configuration),
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
    Twig::class => static function (Settings $settings, \Twig\Profiler\Profile $profile): Twig {
        $twig = Twig::create($settings->get('twig.template_path'), $settings->get('twig.config'));
        $twig->getEnvironment()->addGlobal('base_url', '');
        $twig->getEnvironment()->addGlobal('uinfo', null);
        if ($settings->get('debug')) {
            // Add extensions
            $twig->addExtension(new ProfilerExtension($profile));
            $twig->addExtension(new DebugExtension());
        }

        return $twig;
    },
    SessionConfig::class => static fn (Settings $settings): SessionConfig => new SessionConfig(
        namespace: $settings->get('session.namespace'),
        idleLifetime: $settings->get('session.lifetime'),
        cleanupProbabilityBasisPoints: 100
    ),
    SessionStore::class => static fn (Settings $settings): FileSessionStore => new FileSessionStore(
        $settings->get('session.save_path')
    ),
    SessionManager::class => static fn (
        SessionStore $store,
        SessionConfig $config,
        SystemClock $clock
    ): SessionManager => new SessionManager($store, $config, $clock),
    HttpConfig::class => static function (Settings $settings): HttpConfig {
        $origin = $settings->get('session.origin');
        if ($origin === null) {
            env_required('SESSION_ORIGIN');
        }

        $origin = is_string($origin) ? trim($origin) : $origin;
        $originRequired = $origin !== '';

        return new HttpConfig(
            transport: 'cookie',
            // A fixed origin is still required by the library and used as the JWT issuer.
            canonicalOrigin: $originRequired ? $origin : 'https://session.invalid',
            cookieName: $settings->get('session.name'),
            originRequired: $originRequired
        );
    },
    JwtService::class => static function (Settings $settings, HttpConfig $http, SystemClock $clock): JwtService {
        $key = $settings->get('session.signing_key');
        if ($key === null) {
            env_required('SESSION_SIGNING_KEY');
        }

        return new JwtService(
            $clock,
            $http->canonicalOrigin,
            $settings->get('session.namespace'),
            ['primary' => $key],
            'primary',
            lifetime: $settings->get('session.lifetime')
        );
    },
    SessionMiddleware::class => static fn (
        SessionManager $manager,
        JwtService $jwt,
        HttpConfig $http,
        SystemClock $clock
    ): SessionMiddleware => new SessionMiddleware($manager, $jwt, $http, $clock, new ResponseFactory()),
];
