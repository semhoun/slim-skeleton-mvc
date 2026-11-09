<?php

declare(strict_types=1);

namespace App\Tests\Services;

use App\Renderer\JsonRenderer;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Response;

final class JsonRendererTest extends TestCase
{
    public function testHtmlEscapesMarkupAndPreservesNumericStrings(): void
    {
        $response = (new JsonRenderer())->html(new Response(), ['content' => '</pre><script>alert(1)</script>', 'id' => '00123']);

        self::assertSame('text/html; charset=UTF-8', $response->getHeaderLine('Content-Type'));
        self::assertStringNotContainsString('<script>', (string) $response->getBody());
        self::assertStringContainsString('&lt;script&gt;', (string) $response->getBody());
        self::assertStringContainsString('&quot;00123&quot;', (string) $response->getBody());
    }

    public function testJsonSubstitutesInvalidUtf8AndPreservesStrings(): void
    {
        $response = (new JsonRenderer())->json(new Response(), ['content' => "bad\xFF", 'id' => '00123']);

        self::assertSame('application/json', $response->getHeaderLine('Content-Type'));
        self::assertSame(['content' => "bad\u{FFFD}", 'id' => '00123'], json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testJsonRejectsUnsupportedValuesInsteadOfReturningPartialOutput(): void
    {
        $this->expectException(\JsonException::class);
        (new JsonRenderer())->json(new Response(), ['invalid' => INF]);
    }
}
