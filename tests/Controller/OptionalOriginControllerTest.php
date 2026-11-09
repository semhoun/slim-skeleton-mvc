<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Tests\TestCaseBase;
use PHPUnit\Framework\Attributes\DataProvider;

final class OptionalOriginControllerTest extends TestCaseBase
{
    protected function sessionOrigin(): string
    {
        return '';
    }

    #[DataProvider('requestOrigins')]
    public function testCsrfAllowsLogoutWithoutOriginValidation(?string $origin): void
    {
        $this->authenticate();
        $request = $this->createRequest('POST', '/member/logout')->withoutHeader('Origin');
        if ($origin !== null) {
            $request = $request->withHeader('Origin', $origin);
        }

        $response = $this->app->handle($request);
        self::assertSame(302, $response->getStatusCode());
        self::assertNull($this->session);
        self::assertStringContainsString('Max-Age=0', $response->getHeaderLine('Set-Cookie'));
    }

    public static function requestOrigins(): array
    {
        return [[null], ['null'], ['https://other.example']];
    }

    public function testDisablingOriginStillRequiresCsrf(): void
    {
        $this->authenticate();
        $request = $this->createRequest('POST', '/member/logout')
            ->withoutHeader('Origin')->withoutHeader('X-CSRF-Token');
        self::assertSame(403, $this->app->handle($request)->getStatusCode());
        self::assertTrue($this->session?->data['logged']);
        $response = $this->app->handle($request->withHeader('X-CSRF-Token', 'incorrect'));
        self::assertSame(403, $response->getStatusCode());
        self::assertTrue($this->session?->data['logged']);
    }
}
