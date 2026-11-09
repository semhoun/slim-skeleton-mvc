<?php

declare(strict_types=1);

namespace App\Tests;

use App\Services\Settings;
use DI\Bridge\Slim\Bridge;
use DI\ContainerBuilder;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Monolog\Handler\NullHandler;
use Monolog\Logger;
use Odan\Session\MemorySession;
use Odan\Session\SessionInterface;
use Odan\Session\SessionManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Psr7\Factory\ServerRequestFactory;

abstract class TestCaseBase extends TestCase
{
    protected App $app;
    protected MemorySession $session;
    protected EntityManager $entityManager;

    protected function setUp(): void
    {
        $root = Settings::getAppRoot();
        $loaded = Settings::load();
        $twig = $loaded->get('twig');
        $twig['config'] = ['cache' => false, 'debug' => false];
        $settings = new Settings([
            'debug' => false,
            'trusted_proxies' => $this->trustedProxies(),
            'api_base_path' => $this->apiBasePath(),
            'version' => $loaded->get('version'),
            'twig' => $twig,
        ]);

        $configuration = ORMSetup::createAttributeMetadataConfiguration([$root . '/src/Entity'], true);
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
        $this->entityManager = new EntityManager($connection, $configuration);
        (new SchemaTool($this->entityManager))->createSchema($this->entityManager->getMetadataFactory()->getAllMetadata());
        $this->session = new MemorySession();

        $builder = new ContainerBuilder();
        $builder->addDefinitions($root . '/config/dependencies.php');
        $builder->addDefinitions([
            Settings::class => $settings,
            SessionInterface::class => $this->session,
            SessionManagerInterface::class => $this->session,
            Configuration::class => $configuration,
            Connection::class => $connection,
            EntityManager::class => $this->entityManager,
            Logger::class => new Logger('tests', [new NullHandler()]),
        ]);

        $this->app = Bridge::create($builder->build());
        (require $root . '/config/middleware.php')($this->app);
        (require $root . '/config/routes.php')($this->app);
    }

    protected function tearDown(): void
    {
        $this->entityManager->getConnection()->close();
        parent::tearDown();
    }

    protected function trustedProxies(): array
    {
        return [];
    }

    protected function apiBasePath(): string
    {
        return '/api';
    }

    protected function createRequest(
        string $method,
        string $path,
        array $headers = ['Accept' => 'application/json'],
        array $cookies = [],
        array $serverParams = []
    ): Request {
        $request = (new ServerRequestFactory())->createServerRequest($method, 'http://localhost' . $path, $serverParams);
        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        return $request->withCookieParams($cookies);
    }
}
