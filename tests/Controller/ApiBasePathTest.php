<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Tests\TestCaseBase;

final class ApiBasePathTest extends TestCaseBase
{
    protected function apiBasePath(): string
    {
        return '/v2';
    }

    public function testConfiguredPrefixAppliesToRoutesAndErrorFormat(): void
    {
        self::assertSame(200, $this->app->handle($this->createRequest('GET', '/v2/post'))->getStatusCode());
        self::assertSame(404, $this->app->handle($this->createRequest('GET', '/api/post'))->getStatusCode());
        $response = $this->app->handle($this->createRequest('GET', '/v2/post/999', ['Accept' => 'text/html']));
        self::assertSame(404, $response->getStatusCode());
        self::assertSame('application/json', $response->getHeaderLine('Content-Type'));
    }

    public function testSessionErrorsUseConfiguredPrefix(): void
    {
        $this->authenticate();
        $response = $this->app->handle($this->createRequest('POST', '/v2/post', ['Accept' => 'text/html'])
            ->withoutHeader('X-CSRF-Token')->withParsedBody(['content' => 'forbidden']));
        self::assertSame(403, $response->getStatusCode());
        self::assertSame('application/json', $response->getHeaderLine('Content-Type'));
        self::assertSame(403, json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR)['code']);
    }

    public function testSessionResponsesHonorApplicationBasePath(): void
    {
        $this->app->setBasePath('/nested');
        $headers = ['Cookie' => '__Host-slim-session=not-a-jwt'];
        $response = $this->app->handle($this->createRequest('GET', '/nested/v2/post', $headers));
        self::assertSame(401, $response->getStatusCode());
        self::assertSame('application/json', $response->getHeaderLine('Content-Type'));
        $response = $this->app->handle($this->createRequest('GET', '/nested/member/login?return=home', $headers));
        self::assertSame(302, $response->getStatusCode());
        self::assertSame('/nested/member/login?return=home', $response->getHeaderLine('Location'));
    }
}
