<?php
declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class SessionMiddleware implements Middleware
{
    /**
     * {@inheritdoc}
     */
    public function process(Request $request, RequestHandler $handler): Response
    {
        if (isset($_SESSION['logged']) && $_SESSION['logged'] == true) {
            $request = $request->withAttribute('uinfo', $_SESSION['uinfo']);
        }
        else {
            $request = $request->withAttribute('uinfo', null);
        }

        return $handler->handle($request);
    }
}
