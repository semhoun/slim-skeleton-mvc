<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Semhoun\SlimSession\Http\SessionGuard;
use Semhoun\SlimSession\SessionState;
use Slim\Exception\HttpUnauthorizedException;

final readonly class AuthenticationMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $session = SessionGuard::context($request);
        $user = $session->state() === SessionState::Open ? $session->get('uinfo') : null;
        if (! is_array($user) || ! isset($user['id']) || $session->get('logged') !== true) {
            throw new HttpUnauthorizedException($request, 'Authentication required.');
        }

        return $handler->handle($request);
    }
}
