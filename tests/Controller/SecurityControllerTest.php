<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Post;
use App\Entity\User;
use App\Tests\TestCaseBase;
use PHPUnit\Framework\Attributes\DataProvider;

final class SecurityControllerTest extends TestCaseBase
{
    public function testAnonymousUsersCannotExportAdministrativeData(): void
    {
        foreach (['/admin', '/admin/database', '/admin/database/User', '/admin/database/Post'] as $path) {
            $response = $this->app->handle($this->createRequest('GET', $path));
            self::assertSame(401, $response->getStatusCode());
        }
    }

    public function testAnonymousUsersCannotWrite(): void
    {
        $request = $this->createRequest('POST', '/api/post')
            ->withParsedBody(['content' => 'unauthorized']);
        self::assertSame(401, $this->app->handle($request)->getStatusCode());
        $request = $this->createRequest('DELETE', '/api/post/1');
        self::assertSame(401, $this->app->handle($request)->getStatusCode());
        self::assertSame(0, $this->entityManager->getRepository(Post::class)->count([]));
    }

    public function testLoginPersistsAuthenticationRotatesSessionAndLogoutClearsIt(): void
    {
        $user = new User('operator', password_hash('secure-password-123', PASSWORD_BCRYPT, ['cost' => 4]));
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        $this->app->handle($this->createRequest('GET', '/member/login'));
        $oldId = $this->session->getId();
        $request = $this->createRequest('POST', '/member/login')->withParsedBody([
            'uname' => 'operator', 'pswd' => 'secure-password-123',
        ]);

        $response = $this->app->handle($request);
        self::assertSame(302, $response->getStatusCode());
        self::assertTrue($this->session->get('logged'));
        self::assertSame($user->getId(), $this->session->get('uinfo')['id']);
        self::assertNotSame($oldId, $this->session->getId());

        $home = $this->app->handle($this->createRequest('GET', '/'));
        self::assertStringContainsString('Logout</button>', (string) $home->getBody());
        self::assertSame(200, $this->app->handle($this->createRequest('GET', '/admin/database/User'))->getStatusCode());

        $oldId = $this->session->getId();
        $request = $this->createRequest('POST', '/member/logout');
        self::assertSame(302, $this->app->handle($request)->getStatusCode());
        self::assertNull($this->session->get('logged'));
        self::assertNull($this->session->get('uinfo'));
        self::assertNotSame($oldId, $this->session->getId());
        self::assertSame(401, $this->app->handle($this->createRequest('GET', '/admin'))->getStatusCode());
    }

    public function testLoginFormIsNotCached(): void
    {
        $response = $this->app->handle($this->createRequest('GET', '/member/login'));
        self::assertSame(200, $response->getStatusCode());
        self::assertSame('no-store', $response->getHeaderLine('Cache-Control'));
    }

    public function testLogoutCannotBeTriggeredByGet(): void
    {
        $this->authenticate();
        self::assertSame(405, $this->app->handle($this->createRequest('GET', '/member/logout'))->getStatusCode());
        self::assertTrue($this->session->get('logged'));
    }

    public function testInvalidLoginFieldTypeReturnsBadRequest(): void
    {
        $request = $this->createRequest('POST', '/member/login')->withParsedBody([
            'uname' => ['x'], 'pswd' => 'p',
        ]);
        self::assertSame(400, $this->app->handle($request)->getStatusCode());
    }

    public function testFailedLoginDoesNotAuthenticate(): void
    {
        $request = $this->createRequest('POST', '/member/login')->withParsedBody([
            'uname' => 'missing', 'pswd' => 'invalid',
        ]);
        $response = $this->app->handle($request);
        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('Invalid login/password', (string) $response->getBody());
        self::assertNull($this->session->get('logged'));
    }

    #[DataProvider('historicalAdminPasswords')]
    public function testHistoricalAdminCredentialsAreRejectedUntilMigration(string $password, bool $allowed): void
    {
        $user = new User('admin', password_hash($password, PASSWORD_BCRYPT, ['cost' => 4]));
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        $request = $this->createRequest('POST', '/member/login')->withParsedBody([
            'uname' => 'admin', 'pswd' => $password,
        ]);
        $response = $this->app->handle($request);
        self::assertSame($allowed ? 302 : 200, $response->getStatusCode());
        self::assertSame($allowed, $this->session->get('logged', false));
    }

    public static function historicalAdminPasswords(): array
    {
        return [['admin', false], ['custom-admin-password', true]];
    }

    public function testApiWritesReadsAndDeletesWithRealRoutingAndJsonParsing(): void
    {
        $this->authenticate();
        $request = $this->createRequest('POST', '/api/post', [
            'Content-Type' => 'application/json', 'Accept' => 'text/html',
        ]);
        $request->getBody()->write(json_encode(['title' => 'Example', 'content' => '<img src=x onerror=alert(1)>'], JSON_THROW_ON_ERROR));
        self::assertSame(200, $this->app->handle($request)->getStatusCode());
        $post = $this->entityManager->getRepository(Post::class)->findOneBy(['title' => 'Example']);
        self::assertNotNull($post);

        $response = $this->app->handle($this->createRequest('GET', '/api/post', ['Accept' => 'text/html']));
        self::assertSame('application/json', $response->getHeaderLine('Content-Type'));
        self::assertSame('nosniff', $response->getHeaderLine('X-Content-Type-Options'));
        self::assertCount(1, json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR));
        $response = $this->app->handle($this->createRequest('GET', '/api/post/' . $post->getId()));
        self::assertSame(200, $response->getStatusCode());
        self::assertSame($post->getId(), json_decode((string) $response->getBody(), true)['id']);
        $response = $this->app->handle($this->createRequest('GET', '/blog/' . $post->getId()));
        self::assertSame(200, $response->getStatusCode());
        self::assertStringNotContainsString('<img src=x', (string) $response->getBody());

        $request = $this->createRequest('DELETE', '/api/post/' . $post->getId());
        self::assertSame(200, $this->app->handle($request)->getStatusCode());
        self::assertSame(0, $this->entityManager->getRepository(Post::class)->count([]));
    }

    public function testMissingPostReturnsNotFoundAndApiErrorsStayJson(): void
    {
        $response = $this->app->handle($this->createRequest('GET', '/api/post/999', ['Accept' => 'text/html']));
        self::assertSame(404, $response->getStatusCode());
        self::assertSame('application/json', $response->getHeaderLine('Content-Type'));
        self::assertSame(404, json_decode((string) $response->getBody(), true)['code']);
        self::assertSame(404, $this->app->handle($this->createRequest('GET', '/blog/999'))->getStatusCode());
    }

    public function testRoutingErrorsAreHandledAndInternalDetailsRemainPrivate(): void
    {
        self::assertSame(405, $this->app->handle($this->createRequest('TRACE', '/'))->getStatusCode());
        $response = $this->app->handle($this->createRequest('GET', '/error'));
        self::assertSame(500, $response->getStatusCode());
        $body = (string) $response->getBody();
        self::assertStringNotContainsString('Try error handler', $body);
        self::assertStringNotContainsString('HttpInternalServerErrorException', $body);
        self::assertStringNotContainsString('HomeController', $body);
        self::assertSame(200, $this->app->handle($this->createRequest('GET', '/'))->getStatusCode());
    }

    public function testUntrustedProxyHeadersDoNotChangeGeneratedUrls(): void
    {
        $response = $this->app->handle($this->createRequest('GET', '/', [
            'X-Forwarded-Host' => 'evil.example', 'X-Forwarded-Proto' => 'https',
        ], [], ['REMOTE_ADDR' => '203.0.113.99']));
        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('http://localhost/css/skeleton.css', (string) $response->getBody());
        self::assertStringNotContainsString('evil.example', (string) $response->getBody());
    }

    public function testAdminTableEscapesDataAndBuildsActionLinksSeparately(): void
    {
        $this->authenticate();
        $response = $this->app->handle($this->createRequest('GET', '/admin'));
        self::assertSame(200, $response->getStatusCode());
        $html = (string) $response->getBody();
        self::assertStringContainsString('escape: true', $html);
        self::assertStringContainsString("column.field === 'action'", $html);
        self::assertStringContainsString(".text(data['title'])", $html);
    }

    private function authenticate(): void
    {
        $this->session->set('logged', true);
        $this->session->set('uinfo', ['id' => 1, 'firstname' => 'Test', 'lastname' => 'Operator']);
    }
}
