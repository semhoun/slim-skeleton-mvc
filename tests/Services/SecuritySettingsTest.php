<?php

declare(strict_types=1);

namespace App\Tests\Services;

use App\Services\Settings;
use Odan\Session\PhpSession;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

final class SecuritySettingsTest extends TestCase
{
    private array $environment = [];

    protected function setUp(): void
    {
        env('SKELETON_TEST_PRIME');
        foreach (['DEBUG_MODE', 'SESSION_COOKIE_SECURE', 'TRUSTED_PROXIES'] as $key) {
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
        self::assertTrue($settings->get('session.secure'));
        self::assertTrue($settings->get('session.httponly'));
        self::assertSame('Lax', $settings->get('session.cookie_samesite'));
        self::assertSame([], $settings->get('trusted_proxies'));
        self::assertSame([], $settings->get('tracy.configs.ConsoleAccounts'));
    }

    public function testLocalHttpAndTrustedProxiesRequireExplicitConfiguration(): void
    {
        $_ENV['DEBUG_MODE'] = 'true';
        $_ENV['TRUSTED_PROXIES'] = ' 192.0.2.10, , 2001:db8::1 ';
        $settings = Settings::load();
        self::assertTrue($settings->get('debug'));
        self::assertFalse($settings->get('session.secure'));
        self::assertSame(['192.0.2.10', '2001:db8::1'], $settings->get('trusted_proxies'));
        $_ENV['SESSION_COOKIE_SECURE'] = 'true';
        self::assertTrue(Settings::load()->get('session.secure'));
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testNativeSessionUsesSecureHttpOnlySameSiteCookie(): void
    {
        $session = new PhpSession(Settings::load()->get('session'));
        $session->start();
        try {
            $parameters = session_get_cookie_params();
            self::assertTrue($parameters['secure']);
            self::assertTrue($parameters['httponly']);
            self::assertSame('Lax', $parameters['samesite']);
            self::assertSame('1', ini_get('session.use_strict_mode'));
        } finally {
            $session->destroy();
        }
    }
}
