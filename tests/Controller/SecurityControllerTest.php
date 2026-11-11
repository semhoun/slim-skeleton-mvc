<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Post;
use App\Entity\User;
use App\Tests\TestCaseBase;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Clock\ClockInterface;
use Semhoun\SlimSession\Clock\SystemClock;
use Semhoun\SlimSession\Jwt\JwtService;
use Semhoun\SlimSession\SessionConfig;
use Semhoun\SlimSession\SessionManager;
use Semhoun\SlimSession\SessionRecord;
use Semhoun\SlimSession\Storage\SessionStore;

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

    public function testLoginPersistsAuthenticationAcrossRequestsAndLogoutRevokesIt(): void
    {
        $user = new User('operator', password_hash('secure-password-123', PASSWORD_BCRYPT, ['cost' => 4]));
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        $this->app->handle($this->createRequest('GET', '/member/login'));
        self::assertNull($this->session);
        $request = $this->createRequest('POST', '/member/login')->withParsedBody([
            'uname' => 'operator', 'pswd' => 'secure-password-123',
        ]);

        $response = $this->app->handle($request);
        self::assertSame(302, $response->getStatusCode());
        self::assertTrue($this->session?->data['logged']);
        self::assertSame($user->getId(), $this->session?->data['uinfo']['id']);
        self::assertNotNull($this->session?->id);
        $cookie = $this->cookie;
        $id = $this->session->id;
        $setCookie = $response->getHeaderLine('Set-Cookie');
        self::assertStringStartsWith('__Host-slim-session=', $setCookie);
        foreach (['; Path=/', '; Max-Age=', '; Secure', '; HttpOnly', '; SameSite=Lax'] as $flag) {
            self::assertStringContainsString($flag, $setCookie);
        }
        self::assertStringNotContainsString('Domain=', $setCookie);

        $home = $this->app->handle($this->createRequest('GET', '/'));
        self::assertStringContainsString('Logout</button>', (string) $home->getBody());
        self::assertFalse($home->hasHeader('Set-Cookie'));
        self::assertSame($cookie, $this->cookie);
        self::assertSame($id, $this->session?->id);
        self::assertSame(200, $this->app->handle($this->createRequest('GET', '/admin/database/User'))->getStatusCode());

        $request = $this->createRequest('POST', '/member/logout');
        $response = $this->app->handle($request);
        self::assertSame(302, $response->getStatusCode());
        self::assertStringContainsString('__Host-slim-session=; Path=/; Max-Age=0;', $response->getHeaderLine('Set-Cookie'));
        self::assertNull($this->session);
        self::assertNull($this->cookie);
        self::assertSame(401, $this->app->handle($this->createRequest('GET', '/admin'))->getStatusCode());
        self::assertSame(401, $this->app->handle($this->createRequest('GET', '/admin', [
            'Cookie' => '__Host-slim-session=' . $cookie,
        ]))->getStatusCode());
    }

    public function testLoginFormIsNotCached(): void
    {
        $response = $this->app->handle($this->createRequest('GET', '/member/login'));
        self::assertSame(200, $response->getStatusCode());
        self::assertSame('no-store', $response->getHeaderLine('Cache-Control'));
        self::assertFalse($response->hasHeader('Set-Cookie'));
        self::assertNull($this->session);
        self::assertNull($this->cookie);
    }

    public function testLogoutCannotBeTriggeredByGet(): void
    {
        $this->authenticate();
        self::assertSame(405, $this->app->handle($this->createRequest('GET', '/member/logout'))->getStatusCode());
        self::assertTrue($this->session?->data['logged']);
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
        self::assertNull($this->session);
        self::assertNull($this->cookie);
        self::assertFalse($response->hasHeader('Set-Cookie'));
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
        self::assertSame($allowed, $this->session?->data['logged'] ?? false);
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

    public function testLoginStartsFreshSessionAndRejectsOldCredentials(): void
    {
        $user = new User('operator', password_hash('secure-password-123', PASSWORD_BCRYPT, ['cost' => 4]));
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        $oldSession = $this->seedAgedSession();
        $oldCookie = $this->cookie;
        $response = $this->app->handle($this->createRequest('POST', '/member/login')->withParsedBody([
            'uname' => 'operator', 'pswd' => 'secure-password-123',
        ]));
        self::assertSame(302, $response->getStatusCode());
        self::assertNotNull($this->session);
        self::assertSame([
            'logged' => true,
            'uinfo' => [
                'id' => $user->getId(),
                'firstname' => $user->getFirstName(),
                'lastname' => $user->getLastName(),
                'email' => $user->getEmail(),
            ],
        ], $this->session->data);
        self::assertSame([], $this->session->flash);
        self::assertNotSame($oldSession->id, $this->session->id);
        self::assertNotSame($oldCookie, $this->cookie);
        self::assertNotSame($oldSession->csrfToken, $this->session->csrfToken);
        self::assertNotSame($oldSession->verificationSecret, $this->session->verificationSecret);
        self::assertStringStartsWith('__Host-slim-session=', $response->getHeaderLine('Set-Cookie'));
        $container = $this->app->getContainer();
        self::assertNull($container->get(SessionStore::class)->read(
            $container->get(SessionConfig::class)->namespace,
            $oldSession->id
        ));
        self::assertSame(200, $this->app->handle($this->createRequest('GET', '/admin'))->getStatusCode());
        self::assertSame(403, $this->app->handle($this->createRequest('POST', '/api/post', [
            'X-CSRF-Token' => $oldSession->csrfToken,
        ])->withParsedBody(['content' => 'forbidden']))->getStatusCode());
        self::assertSame(0, $this->entityManager->getRepository(Post::class)->count([]));
        self::assertSame(401, $this->app->handle($this->createRequest('GET', '/admin', [
            'Cookie' => '__Host-slim-session=' . $oldCookie,
        ]))->getStatusCode());
    }

    public function testLoginResetsCreationTimeAndAbsoluteDeadline(): void
    {
        $user = new User('operator', password_hash('secure-password-123', PASSWORD_BCRYPT, ['cost' => 4]));
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        $oldSession = $this->seedAgedSession();
        $container = $this->app->getContainer();
        $clock = $container->get(SystemClock::class);
        $before = $clock->now()->getTimestamp();

        $response = $this->app->handle($this->createRequest('POST', '/member/login')->withParsedBody([
            'uname' => 'operator', 'pswd' => 'secure-password-123',
        ]));

        $after = $clock->now()->getTimestamp();
        self::assertSame(302, $response->getStatusCode());
        self::assertNotNull($this->session);
        self::assertGreaterThan($oldSession->createdAt, $this->session->createdAt);
        self::assertGreaterThanOrEqual($before, $this->session->createdAt);
        self::assertLessThanOrEqual($after, $this->session->createdAt);
        self::assertGreaterThan($oldSession->absoluteExpiresAt, $this->session->absoluteExpiresAt);
        $config = $container->get(SessionConfig::class);
        self::assertSame($this->session->createdAt + $config->absoluteLifetime, $this->session->absoluteExpiresAt);
        self::assertSame($this->session->createdAt, $this->session->lastActivityAt);
        self::assertSame(
            min($this->session->createdAt + $config->idleLifetime, $this->session->absoluteExpiresAt),
            $this->session->idleExpiresAt
        );
    }

    public function testFailedLoginPreservesExistingSession(): void
    {
        $user = new User('operator', password_hash('secure-password-123', PASSWORD_BCRYPT, ['cost' => 4]));
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        $oldSession = $this->seedAgedSession();
        $oldCookie = $this->cookie;

        $response = $this->app->handle($this->createRequest('POST', '/member/login')->withParsedBody([
            'uname' => 'operator', 'pswd' => 'incorrect-password',
        ]));

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('Invalid login/password', (string) $response->getBody());
        self::assertFalse($response->hasHeader('Set-Cookie'));
        self::assertSame($oldCookie, $this->cookie);
        self::assertNotNull($this->session);
        self::assertSame($oldSession->id, $this->session->id);
        self::assertSame($oldSession->data, $this->session->data);
        self::assertSame($oldSession->flash, $this->session->flash);
        self::assertSame($oldSession->createdAt, $this->session->createdAt);
        self::assertSame($oldSession->absoluteExpiresAt, $this->session->absoluteExpiresAt);
        self::assertSame($oldSession->verificationSecret, $this->session->verificationSecret);
        self::assertSame($oldSession->csrfToken, $this->session->csrfToken);
        self::assertSame(200, $this->app->handle($this->createRequest('GET', '/admin'))->getStatusCode());
    }

    private function seedAgedSession(): SessionRecord
    {
        $container = $this->app->getContainer();
        $now = $container->get(SystemClock::class)->now();
        $clock = new class ($now->modify('-120 seconds')) implements ClockInterface {
            public function __construct(private readonly \DateTimeImmutable $time)
            {
            }

            public function now(): \DateTimeImmutable
            {
                return $this->time;
            }
        };
        // Only seeding uses historical time; requests retain the real application clock and store.
        $manager = new SessionManager(
            $container->get(SessionStore::class),
            $container->get(SessionConfig::class),
            $clock
        );
        $context = $manager->create([
            'logged' => true,
            'uinfo' => ['id' => 999, 'firstname' => 'Previous', 'lastname' => 'Account'],
            'old_data' => ['private' => 'must not survive login'],
        ], $now->getTimestamp() + 300);
        $context->flash('old_notice', 'must not survive login');
        $context->saveAndClose();
        $this->session = $context->record();
        self::assertNotNull($this->session);
        $this->cookie = $container->get(JwtService::class)->issue(
            $this->session->id,
            $this->session->absoluteExpiresAt,
            $this->session->verificationSecret
        );

        return $this->session;
    }

    #[DataProvider('invalidMutationProofs')]
    public function testAuthenticatedMutationsRejectInvalidOriginOrCsrf(?string $origin, ?string $csrf): void
    {
        $this->authenticate();
        $headers = [];
        if ($origin !== null && $origin !== 'current') {
            $headers['origin'] = $origin;
        }
        if ($csrf !== null && $csrf !== 'current') {
            $headers['x-csrf-token'] = $csrf;
        }
        $request = $this->createRequest('POST', '/api/post', $headers)->withParsedBody(['content' => 'forbidden']);
        if ($origin === null) {
            $request = $request->withoutHeader('Origin');
        }
        if ($csrf === null) {
            $request = $request->withoutHeader('X-CSRF-Token');
        }
        $response = $this->app->handle($request);
        self::assertSame(403, $response->getStatusCode());
        self::assertSame('application/json', $response->getHeaderLine('Content-Type'));
        self::assertSame('no-store', $response->getHeaderLine('Cache-Control'));
        self::assertSame('nosniff', $response->getHeaderLine('X-Content-Type-Options'));
        $error = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(403, $error['code']);
        self::assertSame('403 Forbidden', $error['title']);
        self::assertSame('Forbidden', $error['message']);
        self::assertArrayNotHasKey('trace', $error);
        self::assertSame(0, $this->entityManager->getRepository(Post::class)->count([]));
        self::assertTrue($this->session?->data['logged']);
    }

    public static function invalidMutationProofs(): array
    {
        return [
            'wrong origin' => ['https://evil.example', 'current'],
            'missing origin' => [null, 'current'],
            'missing csrf' => ['current', null],
            'wrong csrf' => ['current', 'incorrect'],
        ];
    }

    public function testFormCsrfIsAcceptedWithoutHeader(): void
    {
        $this->authenticate();
        $request = $this->createRequest('POST', '/member/logout')
            ->withoutHeader('X-CSRF-Token')
            ->withParsedBody(['_csrf' => $this->session?->csrfToken]);
        self::assertSame(302, $this->app->handle($request)->getStatusCode());
        self::assertNull($this->session);
        self::assertNull($this->cookie);
    }

    public function testExplicitInvalidCsrfHeaderOverridesValidFormToken(): void
    {
        $this->authenticate();
        $request = $this->createRequest('POST', '/member/logout', [
            'origin' => 'http://localhost', 'x-csrf-token' => 'incorrect',
        ])->withParsedBody(['_csrf' => $this->session?->csrfToken]);
        self::assertSame('incorrect', $request->getHeaderLine('X-CSRF-Token'));
        self::assertSame(403, $this->app->handle($request)->getStatusCode());
        self::assertTrue($this->session?->data['logged']);
    }

    #[DataProvider('publicBrowserPages')]
    public function testRevokedCookieRecoversPublicPages(string $path): void
    {
        $this->authenticate();
        $cookie = $this->cookie;
        $this->app->handle($this->createRequest('POST', '/member/logout'));
        $response = $this->app->handle($this->createRequest('GET', $path, [
            'Accept' => 'text/html', 'Cookie' => '__Host-slim-session=' . $cookie,
        ]));
        self::assertSame(302, $response->getStatusCode());
        self::assertSame($path, $response->getHeaderLine('Location'));
        self::assertStringContainsString('Max-Age=0', $response->getHeaderLine('Set-Cookie'));
        self::assertSame('no-store', $response->getHeaderLine('Cache-Control'));
        self::assertSame(200, $this->app->handle($this->createRequest('GET', $path))->getStatusCode());
    }

    public static function publicBrowserPages(): array
    {
        return [['/'], ['/member/login?return=home'], ['/api_info']];
    }

    public function testMalformedCookieRecoversLoginWithoutReplayingPost(): void
    {
        $headers = ['Cookie' => '__Host-slim-session=not-a-jwt'];
        $response = $this->app->handle($this->createRequest('POST', '/member/login', $headers)
            ->withParsedBody(['uname' => 'operator', 'pswd' => 'secure-password-123']));
        self::assertSame(401, $response->getStatusCode());
        self::assertFalse($response->hasHeader('Location'));
        $response = $this->app->handle($this->createRequest('GET', '/admin', $headers));
        self::assertSame(401, $response->getStatusCode());
        self::assertFalse($response->hasHeader('Location'));
        $response = $this->app->handle($this->createRequest('GET', '/member/login', $headers));
        self::assertSame(302, $response->getStatusCode());
        self::assertSame('/member/login', $response->getHeaderLine('Location'));
    }

    public function testRevokedCookieReturnsJsonAndDeletionHeaderForApi(): void
    {
        $this->authenticate();
        $cookie = $this->cookie;
        $this->app->handle($this->createRequest('POST', '/member/logout'));
        $response = $this->app->handle($this->createRequest('GET', '/api/post', [
            'Accept' => 'text/html', 'Cookie' => '__Host-slim-session=' . $cookie,
        ]));
        self::assertSame(401, $response->getStatusCode());
        self::assertSame('application/json', $response->getHeaderLine('Content-Type'));
        self::assertSame(401, json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR)['code']);
        self::assertStringContainsString('Max-Age=0', $response->getHeaderLine('Set-Cookie'));
        self::assertFalse($response->hasHeader('Location'));
    }

    public function testEmptySessionErrorStatusesKeepTheirCodeAndSafeJsonFormat(): void
    {
        foreach ([401, 403, 409, 503] as $status) {
            $this->app->get('/api/session-error-' . $status, static fn (
                \Psr\Http\Message\ServerRequestInterface $request,
                \Psr\Http\Message\ResponseInterface $response
            ): \Psr\Http\Message\ResponseInterface => $response->withStatus($status)
                ->withHeader('Retry-After', '3'));
        }

        foreach ([401, 403, 409, 503] as $status) {
            $response = $this->app->handle($this->createRequest('GET', '/api/session-error-' . $status));
            self::assertSame($status, $response->getStatusCode());
            self::assertSame('application/json', $response->getHeaderLine('Content-Type'));
            self::assertSame('3', $response->getHeaderLine('Retry-After'));
            $error = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
            self::assertSame($status, $error['code']);
            self::assertArrayNotHasKey('trace', $error);
            if ($status === 503) {
                self::assertSame('An internal server error occurred.', $error['message']);
            }
        }
    }
}
