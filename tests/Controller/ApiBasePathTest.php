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
}
