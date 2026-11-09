<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Tests\TestCaseBase;

final class ProxyControllerTest extends TestCaseBase
{
    protected function trustedProxies(): array
    {
        return ['192.0.2.10'];
    }

    public function testOnlyConfiguredProxyMayOverrideOrigin(): void
    {
        foreach (['192.0.2.10' => 'https://proxy.example', '203.0.113.99' => 'http://localhost'] as $ip => $origin) {
            $request = $this->createRequest('GET', '/', [
                'X-Forwarded-Host' => 'proxy.example', 'X-Forwarded-Proto' => 'https',
            ], [], ['REMOTE_ADDR' => $ip]);
            $response = $this->app->handle($request);
            self::assertSame(200, $response->getStatusCode());
            self::assertStringContainsString($origin . '/css/skeleton.css', (string) $response->getBody());
        }
    }
}
