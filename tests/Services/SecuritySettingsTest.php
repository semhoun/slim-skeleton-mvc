<?php

declare(strict_types=1);

namespace App\Tests\Services;

use App\Services\Settings;
use DI\ContainerBuilder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Semhoun\SlimSession\Http\HttpConfig;
use Semhoun\SlimSession\Jwt\JwtService;
use Semhoun\SlimSession\SessionConfig;

final class SecuritySettingsTest extends TestCase
{
    private array $environment = [];

    protected function setUp(): void
    {
        env('SKELETON_TEST_PRIME');
        $keys = [
            'DEBUG_MODE', 'TRACY_API_PROFILER', 'SESSION_COOKIE_SECURE',
            'SESSION_SIGNING_KEY', 'SESSION_ORIGIN', 'TRUSTED_PROXIES',
        ];
        foreach ($keys as $key) {
            $this->environment[$key] = [array_key_exists($key, $_ENV), $_ENV[$key] ?? null, getenv($key)];
            unset($_ENV[$key]);
            putenv($key);
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->environment as $key => [$present, $value, $process]) {
            if ($present) {
                $_ENV[$key] = $value;
            } else {
                unset($_ENV[$key]);
            }
            putenv($process === false ? $key : $key . '=' . $process);
        }
        parent::tearDown();
    }

    public function testDefaultsAreSafeWithoutEnvironmentConfiguration(): void
    {
        $settings = Settings::load();
        self::assertFalse($settings->get('debug'));
        self::assertFalse($settings->get('twig.config.debug'));
        self::assertFalse($settings->get('twig.config.auto_reload'));
        self::assertSame('__Host-slim-session', $settings->get('session.name'));
        self::assertSame(Settings::getAppRoot() . '/var/sessions', $settings->get('session.save_path'));
        self::assertSame([], $settings->get('trusted_proxies'));
        self::assertSame([], $settings->get('tracy.configs.ConsoleAccounts'));
        self::assertFalse($settings->get('tracy.configs.AjaxProfiler.enabled'));
        self::assertSame([], $settings->get('tracy.configs.AjaxProfiler.origins'));
        self::assertSame(
            Settings::getAppRoot() . '/var/tmp/api-profiles',
            $settings->get('tracy.configs.AjaxProfiler.storageDirectory')
        );
    }

    public function testApiProfilerRequiresExplicitOptInEvenInDebugMode(): void
    {
        $_ENV['DEBUG_MODE'] = 'true';
        self::assertFalse(Settings::load()->get('tracy.configs.AjaxProfiler.enabled'));

        $_ENV['TRACY_API_PROFILER'] = 'true';
        self::assertTrue(Settings::load()->get('tracy.configs.AjaxProfiler.enabled'));

        $_ENV['TRACY_API_PROFILER'] = '1';
        self::assertFalse(Settings::load()->get('tracy.configs.AjaxProfiler.enabled'));
    }

    public function testDebugCannotDisableCookieSecurityAndTrustedProxiesAreExplicit(): void
    {
        $_ENV['DEBUG_MODE'] = 'true';
        $_ENV['SESSION_COOKIE_SECURE'] = 'false';
        $_ENV['SESSION_ORIGIN'] = 'http://localhost:8080';
        $_ENV['TRUSTED_PROXIES'] = ' 192.0.2.10, , 2001:db8::1 ';
        $settings = Settings::load();
        self::assertTrue($settings->get('debug'));
        self::assertSame(['192.0.2.10', '2001:db8::1'], $settings->get('trusted_proxies'));
        $builder = new ContainerBuilder();
        $builder->addDefinitions(Settings::getAppRoot() . '/config/dependencies.php');
        $http = $builder->build()->get(HttpConfig::class);
        self::assertSame('__Host-slim-session', $http->cookieName);
        self::assertSame('Lax', $http->sameSite);
        self::assertSame('http://localhost:8080', $http->canonicalOrigin);
        self::assertTrue($http->originRequired);
        self::assertTrue($http->csrfRequired);
    }

    #[DataProvider('emptyOrigins')]
    public function testExplicitEmptyOriginDisablesOnlyOriginValidation(string $origin): void
    {
        $_ENV['SESSION_ORIGIN'] = $origin;
        $_ENV['SESSION_SIGNING_KEY'] = str_repeat('test-secret-', 4);
        $builder = new ContainerBuilder();
        $builder->addDefinitions(Settings::getAppRoot() . '/config/dependencies.php');
        $container = $builder->build();
        $http = $container->get(HttpConfig::class);
        self::assertFalse($http->originRequired);
        self::assertTrue($http->csrfRequired);
        self::assertSame('https://session.invalid', $http->canonicalOrigin);
        self::assertInstanceOf(JwtService::class, $container->get(JwtService::class));
    }

    public static function emptyOrigins(): array
    {
        return [[''], ['   '], ["\t"]];
    }

    public function testInvalidNonEmptyOriginIsStillRejected(): void
    {
        $_ENV['SESSION_ORIGIN'] = 'https://example.test/path';
        $builder = new ContainerBuilder();
        $builder->addDefinitions(Settings::getAppRoot() . '/config/dependencies.php');
        $this->expectException(\InvalidArgumentException::class);
        $builder->build()->get(HttpConfig::class);
    }

    public function testSessionOriginIsRequired(): void
    {
        $builder = new ContainerBuilder();
        $builder->addDefinitions(Settings::getAppRoot() . '/config/dependencies.php');
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('SESSION_ORIGIN');
        $builder->build()->get(HttpConfig::class);
    }

    public function testSessionSigningKeyIsRequired(): void
    {
        $_ENV['SESSION_ORIGIN'] = 'http://localhost:8080';
        $builder = new ContainerBuilder();
        $builder->addDefinitions(Settings::getAppRoot() . '/config/dependencies.php');
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('SESSION_SIGNING_KEY');
        $builder->build()->get(JwtService::class);
    }

    public function testShortSessionSigningKeyIsRejected(): void
    {
        $_ENV['SESSION_ORIGIN'] = 'http://localhost:8080';
        $_ENV['SESSION_SIGNING_KEY'] = 'too-short';
        $builder = new ContainerBuilder();
        $builder->addDefinitions(Settings::getAppRoot() . '/config/dependencies.php');
        $this->expectException(\InvalidArgumentException::class);
        $builder->build()->get(JwtService::class);
    }

    public function testSessionIdleLifetimeIsPreserved(): void
    {
        $builder = new ContainerBuilder();
        $builder->addDefinitions(Settings::getAppRoot() . '/config/dependencies.php');
        self::assertSame(7200, $builder->build()->get(SessionConfig::class)->idleLifetime);
    }

    public function testJwtRenewalUsesHalfTheActualTokenLifetime(): void
    {
        $_ENV['SESSION_ORIGIN'] = 'http://localhost:8080';
        $_ENV['SESSION_SIGNING_KEY'] = str_repeat('test-secret-', 4);
        $builder = new ContainerBuilder();
        $builder->addDefinitions(Settings::getAppRoot() . '/config/dependencies.php');
        $jwt = $builder->build()->get(JwtService::class);

        self::assertSame(7200, $jwt->lifetime);
        self::assertNull($jwt->renewalWindow);
        self::assertSame(4600, $jwt->refreshAt(1000, 8200));
        self::assertSame(1150, $jwt->refreshAt(1000, 1300));
        self::assertSame(1151, $jwt->refreshAt(1000, 1301));
    }
}
