<?php

declare(strict_types=1);

namespace App\Tests\Services;

use App\Controller\Api\PostController;
use App\Controller\BlogController;
use App\Entity\Post;
use App\Renderer\JsonRenderer;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpInternalServerErrorException;
use Slim\Exception\HttpNotFoundException;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Response;
use Slim\Views\Twig;

final class PostControllerTest extends TestCase
{
    public static function malformedBodies(): iterable
    {
        yield [null];
        yield [(object) ['content' => 'text']];
        yield [[]];
        foreach ([null, false, 123, [], '', '   '] as $content) {
            yield [['content' => $content]];
        }
        foreach ([true, 123, [], (object) []] as $title) {
            yield [['content' => 'text', 'title' => $title]];
        }
    }

    #[DataProvider('malformedBodies')]
    public function testAddRejectsMalformedInput(mixed $body): void
    {
        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects(self::never())->method('persist');
        $controller = new PostController(new JsonRenderer(), $entityManager);
        $request = (new ServerRequestFactory())->createServerRequest('POST', '/api/post')->withParsedBody($body);

        $this->expectException(HttpBadRequestException::class);
        $controller->add($request, new Response());
    }

    public function testAddDoesNotRequireSlugAndAcceptsZeroContentWithNullTitle(): void
    {
        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects(self::once())->method('persist')->with(self::callback(
            static fn (Post $post): bool => $post->getContent() === '0' && $post->getTitle() === null
        ));
        $entityManager->expects(self::once())->method('flush');
        $request = (new ServerRequestFactory())->createServerRequest('POST', '/api/post')->withParsedBody(['content' => '0', 'title' => null]);
        $response = (new PostController(new JsonRenderer(), $entityManager))->add($request, new Response());

        self::assertSame('application/json', $response->getHeaderLine('Content-Type'));
        self::assertSame('[]', (string) $response->getBody());
    }

    public static function missingPostMethods(): iterable
    {
        yield ['get'];
        yield ['delete'];
    }

    #[DataProvider('missingPostMethods')]
    public function testMissingPostReturnsNotFoundUsingRouteId(string $method): void
    {
        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::once())->method('find')->with(42)->willReturn(null);
        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->method('getRepository')->with(Post::class)->willReturn($repository);
        $entityManager->expects(self::never())->method('remove');
        $entityManager->expects(self::never())->method('flush');
        $controller = new PostController(new JsonRenderer(), $entityManager);
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/api/post/42');

        $this->expectException(HttpNotFoundException::class);
        $controller->$method($request, new Response(), id: 42);
    }

    public function testGetAllReturnsJsonEvenForHtmlAccept(): void
    {
        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findAll')->willReturn([]);
        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->method('getRepository')->willReturn($repository);
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/api/post')->withHeader('Accept', 'text/html');
        $response = (new PostController(new JsonRenderer(), $entityManager))->getAll($request, new Response());

        self::assertSame('application/json', $response->getHeaderLine('Content-Type'));
        self::assertSame('[]', (string) $response->getBody());
    }

    public function testGetReturnsAnExistingPostAsJson(): void
    {
        $post = new Post('text', 'title');
        (new \ReflectionProperty(Post::class, 'id'))->setValue($post, 42);
        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::once())->method('find')->with(42)->willReturn($post);
        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->method('getRepository')->willReturn($repository);
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/api/post/42');
        $response = (new PostController(new JsonRenderer(), $entityManager))->get($request, new Response(), id: 42);

        self::assertSame('application/json', $response->getHeaderLine('Content-Type'));
        self::assertSame(['id' => 42, 'title' => 'title', 'content' => 'text'], json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testDeleteRemovesAnExistingPost(): void
    {
        $post = new Post('text');
        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::once())->method('find')->with(42)->willReturn($post);
        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->method('getRepository')->willReturn($repository);
        $entityManager->expects(self::once())->method('remove')->with($post);
        $entityManager->expects(self::once())->method('flush');
        $request = (new ServerRequestFactory())->createServerRequest('DELETE', '/api/post/42');
        $response = (new PostController(new JsonRenderer(), $entityManager))->delete($request, new Response(), id: 42);

        self::assertSame('application/json', $response->getHeaderLine('Content-Type'));
        self::assertSame('[]', (string) $response->getBody());
    }

    public function testInternalErrorsPreserveTheirCause(): void
    {
        $cause = new \RuntimeException('database failure');
        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->method('getRepository')->willThrowException($cause);
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/api/post/42');

        try {
            (new PostController(new JsonRenderer(), $entityManager))->get($request, new Response(), id: 42);
            self::fail('Expected an internal server error');
        } catch (HttpInternalServerErrorException $exception) {
            self::assertSame($cause, $exception->getPrevious());
        }
    }

    public function testBlogUsesRouteId(): void
    {
        $post = new Post('text');
        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::once())->method('find')->with(42)->willReturn($post);
        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->method('getRepository')->willReturn($repository);
        $response = new Response();
        $twig = $this->createMock(Twig::class);
        $twig->expects(self::once())->method('render')->with($response, 'blog.twig', ['post' => $post])->willReturn($response);
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/blog/42');

        self::assertSame($response, (new BlogController($twig, $entityManager))->view($request, $response, id: 42));
    }
}
