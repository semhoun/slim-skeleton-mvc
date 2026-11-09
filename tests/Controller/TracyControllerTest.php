<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Services\Settings;
use DI\Bridge\Slim\Bridge;
use DI\ContainerBuilder;
use DirectoryIterator;
use Monolog\Handler\NullHandler;
use Monolog\Logger;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Semhoun\SlimSession\Clock\SystemClock;
use Semhoun\SlimSession\Storage\InMemorySessionStore;
use Semhoun\SlimSession\Storage\SessionStore;
use Slim\App;
use Slim\Exception\HttpBadRequestException;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Views\Twig;
use SlimTracy\Profiling\RequestProfiler;
use Tracy\Debugger;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class TracyControllerTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = sys_get_temp_dir() . '/slim-tracy-controller-' . bin2hex(random_bytes(8));
        self::assertTrue(mkdir($this->directory, 0700));
    }

    protected function tearDown(): void
    {
        foreach (new DirectoryIterator($this->directory) as $file) {
            if (!$file->isDot()) {
                unlink($file->getPathname());
            }
        }
        rmdir($this->directory);
        parent::tearDown();
    }

    #[DataProvider('basePaths')]
    public function testJsonSuccessIsUnchangedAndRetrievable(string $basePath): void
    {
        $app = $this->createApp(basePath: $basePath);
        $response = $app->handle($this->request($basePath . '/api/tracy-test/success'));

        self::assertSame(201, $response->getStatusCode());
        self::assertSame('application/json', $response->getHeaderLine('Content-Type'));
        self::assertSame('{"ok":true}', (string) $response->getBody());
        self::assertSame('preserved', $response->getHeaderLine('X-Test-Response'));
        self::assertSame('no-store', $response->getHeaderLine('Cache-Control'));
        self::assertSame('nosniff', $response->getHeaderLine('X-Content-Type-Options'));
        $this->assertProfile($app, $response, $basePath . '/api/tracy-test/success', 201, $basePath);
    }

    public static function basePaths(): array
    {
        return ['root' => [''], 'mounted' => ['/mounted']];
    }

    #[DataProvider('handledErrors')]
    public function testHandledJsonErrorsRetainStatusBodyAndCorrelation(string $route, int $status, string $message): void
    {
        $app = $this->createApp();
        // API errors must stay JSON even when the client prefers HTML.
        $response = $app->handle($this->request('/api/tracy-test/' . $route)->withHeader('Accept', 'text/html'));

        self::assertSame($status, $response->getStatusCode());
        self::assertStringStartsWith('application/json', $response->getHeaderLine('Content-Type'));
        $body = json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame($status, $body['code']);
        self::assertSame($message, $body['message']);
        self::assertSame('no-store', $response->getHeaderLine('Cache-Control'));
        self::assertSame('nosniff', $response->getHeaderLine('X-Content-Type-Options'));
        $this->assertProfile($app, $response, '/api/tracy-test/' . $route, $status);
    }

    public static function handledErrors(): array
    {
        return [
            'client error' => ['bad-request', 400, 'Invalid test input.'],
            'server error' => ['failure', 500, 'Test failure.'],
        ];
    }

    #[DataProvider('inactiveModes')]
    public function testInactiveProfilerHasNoEndpointsOrCorrelation(bool $debug, bool $enabled): void
    {
        $app = $this->createApp($debug, $enabled);
        $response = $app->handle($this->request('/api/tracy-test/success'));
        self::assertSame(201, $response->getStatusCode());
        self::assertSame('{"ok":true}', (string) $response->getBody());
        self::assertFalse($response->hasHeader(RequestProfiler::HEADER));

        foreach (['', '/profiles', '/profiles/' . str_repeat('a', 32)] as $suffix) {
            $response = $app->handle($this->request('/_tracy/api' . $suffix));
            self::assertSame(404, $response->getStatusCode());
            self::assertFalse($response->hasHeader(RequestProfiler::HEADER));
        }
        self::assertSame([], array_values(array_diff(scandir($this->directory), ['.', '..'])));
    }

    public static function inactiveModes(): array
    {
        return ['disabled in debug' => [true, false], 'production despite opt-in' => [false, true]];
    }

    private function createApp(bool $debug = true, bool $enabled = true, string $basePath = ''): App
    {
        $root = Settings::getAppRoot();
        $tracy = require $root . '/config/settings/tracy.php';
        $tracy['showDoctrinePanel'] = 0;
        unset($tracy['configs']['Container']['Doctrine']);
        $tracy['configs']['ShowBar'] = 0;
        // Resolve development mode without installing native error/shutdown handlers over PHPUnit's.
        Debugger::$productionMode = !$debug;
        unset($tracy['configs']['Debugger']);
        $tracy['configs']['AjaxProfiler'] = array_replace($tracy['configs']['AjaxProfiler'] ?? [], [
            'enabled' => $enabled,
            'origins' => [],
            'path' => '/_tracy/api',
            'storageDirectory' => $this->directory,
        ]);
        $session = require $root . '/config/settings/session.php';
        $settings = new Settings([
            'debug' => $debug,
            'trusted_proxies' => [],
            'api_base_path' => '/api',
            'tracy' => $tracy,
            'session' => array_replace($session, [
                'origin' => 'http://localhost',
                'signing_key' => 'test-only-session-signing-key-at-least-32-bytes',
            ]),
        ]);
        $builder = new ContainerBuilder();
        $builder->addDefinitions($root . '/config/dependencies.php');
        $builder->addDefinitions([
            Settings::class => $settings,
            Twig::class => static fn (): Twig => Twig::create($root . '/tmpl', ['cache' => false]),
            SessionStore::class => static fn (SystemClock $clock): InMemorySessionStore => new InMemorySessionStore($clock),
            Logger::class => new Logger('tracy-tests', [new NullHandler()]),
        ]);
        $app = Bridge::create($builder->build());
        $app->setBasePath($basePath);
        (require $root . '/config/middleware.php')($app);
        (require $root . '/config/routes.php')($app);
        $app->get('/api/tracy-test/success', function (ResponseInterface $response): ResponseInterface {
            $response->getBody()->write('{"ok":true}');

            return $response->withStatus(201)->withHeader('Content-Type', 'application/json')
                ->withHeader('X-Test-Response', 'preserved');
        });
        $app->get('/api/tracy-test/bad-request', function (ServerRequestInterface $request): never {
            throw new HttpBadRequestException($request, 'Invalid test input.');
        });
        $app->get('/api/tracy-test/failure', function (): never {
            throw new RuntimeException('Test failure.');
        });

        return $app;
    }

    private function request(string $path): ServerRequestInterface
    {
        return (new ServerRequestFactory())->createServerRequest('GET', 'http://localhost' . $path)
            ->withHeader('Accept', 'application/json');
    }

    private function assertProfile(
        App $app,
        ResponseInterface $response,
        string $path,
        int $status,
        string $basePath = ''
    ): void {
        $id = $response->getHeaderLine(RequestProfiler::HEADER);
        self::assertMatchesRegularExpression('/^[a-f0-9]{32}$/D', $id);
        $profileResponse = $app->handle($this->request($basePath . '/_tracy/api/profiles/' . $id));
        self::assertSame(200, $profileResponse->getStatusCode());
        self::assertStringStartsWith('application/json', $profileResponse->getHeaderLine('Content-Type'));
        self::assertSame('no-store', $profileResponse->getHeaderLine('Cache-Control'));
        self::assertFalse($profileResponse->hasHeader(RequestProfiler::HEADER));
        $profile = json_decode((string) $profileResponse->getBody(), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame($id, $profile['id']);
        self::assertSame($path, $profile['path']);
        self::assertSame('GET', $profile['method']);
        self::assertSame($status, $profile['status']);
    }
}
