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
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;
use Semhoun\SlimSession\Clock\SystemClock;
use Semhoun\SlimSession\Http\HttpConfig;
use Semhoun\SlimSession\Jwt\JwtService;
use Semhoun\SlimSession\SessionConfig;
use Semhoun\SlimSession\SessionManager;
use Semhoun\SlimSession\SessionRecord;
use Semhoun\SlimSession\Storage\InMemorySessionStore;
use Semhoun\SlimSession\Storage\SessionStore;
use Slim\App;
use Slim\Psr7\Factory\ServerRequestFactory;

abstract class TestCaseBase extends TestCase
{
    protected App $app;
    protected ?SessionRecord $session = null;
    protected ?string $cookie = null;
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
            'session' => array_replace($loaded->get('session'), [
            'origin' => $this->sessionOrigin(),
                'signing_key' => 'test-only-session-signing-key-at-least-32-bytes',
            ]),
        ]);

        $configuration = ORMSetup::createAttributeMetadataConfiguration([$root . '/src/Entity'], true);
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
        $this->entityManager = new EntityManager($connection, $configuration);
        (new SchemaTool($this->entityManager))->createSchema($this->entityManager->getMetadataFactory()->getAllMetadata());
        $this->session = null;
        $this->cookie = null;

        $builder = new ContainerBuilder();
        $builder->addDefinitions($root . '/config/dependencies.php');
        $builder->addDefinitions([
            Settings::class => $settings,
            SessionStore::class => static fn (SystemClock $clock): InMemorySessionStore => new InMemorySessionStore($clock),
            Configuration::class => $configuration,
            Connection::class => $connection,
            EntityManager::class => $this->entityManager,
            Logger::class => new Logger('tests', [new NullHandler()]),
        ]);

        $this->app = Bridge::create($builder->build());
        (require $root . '/config/middleware.php')($this->app);
        $test = $this;
        $container = $this->app->getContainer();
        $this->app->add(function (Request $request, RequestHandlerInterface $handler) use ($test, $container): Response {
            $response = $handler->handle($request);
            $name = $container->get(HttpConfig::class)->cookieName;
            foreach ($response->getHeader('Set-Cookie') as $header) {
                [$cookieName, $value] = explode('=', explode(';', $header, 2)[0], 2);
                if ($cookieName === $name) {
                    $test->cookie = $value === '' ? null : $value;
                }
            }
            $test->session = null;
            if ($test->cookie !== null) {
                $token = $container->get(JwtService::class)->verify($test->cookie);
                $test->session = $container->get(SessionStore::class)->read(
                    $container->get(SessionConfig::class)->namespace,
                    $token->sessionId
                );
                if ($test->session !== null) {
                    TestCase::assertSame($test->session->verificationSecret, $token->verificationSecret);
                }
            }

            return $response;
        });
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

    protected function sessionOrigin(): string
    {
        return 'http://localhost';
    }

    protected function authenticate(): void
    {
        $container = $this->app->getContainer();
        $context = $container->get(SessionManager::class)->create([
            'logged' => true,
            'uinfo' => ['id' => 1, 'firstname' => 'Test', 'lastname' => 'Operator'],
        ]);
        $context->saveAndClose();
        $this->session = $context->record();
        self::assertNotNull($this->session);
        $this->cookie = $container->get(JwtService::class)->issue(
            $this->session->id,
            $this->session->absoluteExpiresAt,
            $this->session->verificationSecret
        );
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

        $cookieName = $this->app->getContainer()->get(HttpConfig::class)->cookieName;
        if ($this->cookie !== null && !array_key_exists($cookieName, $cookies)) {
            $cookies[$cookieName] = $this->cookie;
        }
        if (!$request->hasHeader('Cookie') && $cookies !== []) {
            $pairs = [];
            foreach ($cookies as $name => $value) {
                $pairs[] = $name . '=' . $value;
            }
            $request = $request->withHeader('Cookie', implode('; ', $pairs));
        }
        if (!in_array(strtoupper($method), ['GET', 'HEAD', 'OPTIONS'], true)) {
            if (!$request->hasHeader('Origin')) {
                $request = $request->withHeader('Origin', 'http://localhost');
            }
            if (!$request->hasHeader('X-CSRF-Token') && $this->session !== null) {
                $request = $request->withHeader('X-CSRF-Token', $this->session->csrfToken);
            }
        }

        return $request->withCookieParams($cookies);
    }
}
