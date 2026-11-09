<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Semhoun\SlimSession\Http\SessionGuard;
use Semhoun\SlimSession\SessionState;
use Slim\Views\Twig;

final readonly class SessionSecurityMiddleware implements MiddlewareInterface
{
    public function __construct(private Twig $twig)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $session = SessionGuard::context($request);
        $user = $session->state() === SessionState::Open && $session->get('logged') === true
            ? $session->get('uinfo') : null;
        $this->twig->getEnvironment()->addGlobal('uinfo', $user);
        $this->twig->getEnvironment()->addGlobal('csrf_token', $session->record()?->csrfToken);

        $response = $handler->handle($request->withAttribute('uinfo', $user));

        return $response
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withHeader('Cache-Control', 'no-store');
    }
}
