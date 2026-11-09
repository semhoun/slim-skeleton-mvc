<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class SessionFormMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $data = $request->getParsedBody();
        // HTML forms cannot send the session library's CSRF header themselves.
        if (! $request->hasHeader('X-CSRF-Token') && is_array($data) && is_string($data['_csrf'] ?? null)) {
            $request = $request->withHeader('X-CSRF-Token', $data['_csrf']);
        }

        return $handler->handle($request)->withHeader('X-Content-Type-Options', 'nosniff');
    }
}
