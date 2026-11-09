<?php

declare(strict_types=1);

namespace App\Tests\Services;

use App\Renderer\JsonErrorRenderer;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpException;
use Slim\Exception\HttpInternalServerErrorException;
use Slim\Exception\HttpNotFoundException;
use Slim\Psr7\Factory\ServerRequestFactory;

final class JsonErrorRendererTest extends TestCase
{
    public function testInternalErrorsAreGenericButLogged(): void
    {
        $handler = new TestHandler();
        $renderer = new JsonErrorRenderer(new Logger('test', [$handler]));
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/');

        foreach ([new \RuntimeException('database password secret', 404), new HttpInternalServerErrorException($request, 'database password secret')] as $exception) {
            $data = json_decode($renderer($exception, false), true, 512, JSON_THROW_ON_ERROR);
            self::assertSame(500, $data['code']);
            self::assertSame('500 Internal Server Error', $data['title']);
            self::assertSame('An internal server error occurred.', $data['message']);
            self::assertArrayNotHasKey('type', $data);
        }

        self::assertCount(2, $handler->getRecords());
        self::assertStringContainsString('database password secret', $handler->getRecords()[0]->message);
        self::assertArrayHasKey('trace', $handler->getRecords()[0]->context);
    }

    public function testClientErrorsRemainReadableAndInvalidUtf8IsReplaced(): void
    {
        $renderer = new JsonErrorRenderer(new Logger('test'));
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/');

        foreach ([new HttpBadRequestException($request, "invalid content\xFF"), new HttpNotFoundException($request, "missing post\xFF")] as $exception) {
            $data = json_decode($renderer($exception, false), true, 512, JSON_THROW_ON_ERROR);
            self::assertSame($exception->getCode(), $data['code']);
            self::assertSame($exception->getTitle(), $data['title']);
            self::assertStringEndsWith("\u{FFFD}", $data['message']);
        }
    }

    public function testDebugDetailsRemainAvailableForInvalidUtf8Exception(): void
    {
        $renderer = new JsonErrorRenderer(new Logger('test'));
        $data = json_decode($renderer(new \RuntimeException("secret\xFF"), true), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame("secret\u{FFFD}", $data['message']);
        self::assertSame(\RuntimeException::class, $data['type']);
        self::assertArrayHasKey('trace', $data);
    }

    public function testServiceUnavailableKeepsItsHttpCodeWithoutLeakingDetails(): void
    {
        $renderer = new JsonErrorRenderer(new Logger('test'));
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/');
        $exception = new HttpException($request, 'private upstream', 503);
        $exception->setTitle('private title');
        $data = json_decode($renderer($exception, false), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(503, $data['code']);
        self::assertSame('503 Server Error', $data['title']);
        self::assertSame('An internal server error occurred.', $data['message']);
    }
}
