<?php

declare(strict_types=1);

namespace App\Tests\Services;

use App\Renderer\HtmlErrorRenderer;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpInternalServerErrorException;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Views\Twig;
use Twig\Loader\ArrayLoader;

final class HtmlErrorRendererTest extends TestCase
{
    private function renderer(TestHandler $handler): HtmlErrorRenderer
    {
        $twig = new Twig(new ArrayLoader([
            'error/default.twig' => '{{ title }}|{{ code }}|{{ message }}{% if debug %}|{{ type }}{% endif %}',
            'error/404.twig' => '{{ title }}|{{ code }}|{{ message }}',
        ]), ['autoescape' => 'html']);

        return new HtmlErrorRenderer($twig, new Logger('test', [$handler]));
    }

    public function testInternalErrorsAreGenericWithoutRethrowing(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/');
        $handler = new TestHandler();
        $renderer = $this->renderer($handler);
        foreach ([new \RuntimeException('database secret', 404), new HttpInternalServerErrorException($request, 'database secret')] as $exception) {
            self::assertSame('500 Internal Server Error|500|An internal server error occurred.', $renderer($exception, false));
        }
        self::assertCount(2, $handler->getRecords());
        self::assertStringContainsString('database secret', $handler->getRecords()[0]->message);
    }

    public function testClientMessagesAndDebugDetailsAreEscapedWithInvalidUtf8(): void
    {
        $renderer = $this->renderer(new TestHandler());
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/');
        $html = $renderer(new HttpBadRequestException($request, "<script>\xFF</script>"), false);

        self::assertStringContainsString('400 Bad Request|400|', $html);
        self::assertStringContainsString("&lt;script&gt;\u{FFFD}&lt;/script&gt;", $html);
        self::assertStringContainsString('RuntimeException', $renderer(new \RuntimeException("secret\xFF"), true));
    }
}
