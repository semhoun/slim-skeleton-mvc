<?php

declare(strict_types=1);

namespace App\Middleware;

use Odan\Session\SessionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Views\Twig;

final readonly class SessionSecurityMiddleware implements MiddlewareInterface
{
    public function __construct(private SessionInterface $session, private Twig $twig)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $user = $this->session->get('logged') === true ? $this->session->get('uinfo') : null;
        $this->twig->getEnvironment()->addGlobal('uinfo', $user);

        $response = $handler->handle($request->withAttribute('uinfo', $user));

        return $response
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withHeader('Cache-Control', 'no-store');
    }
}
