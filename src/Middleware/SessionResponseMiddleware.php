<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Renderer\JsonErrorRenderer;
use App\Services\Settings;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Semhoun\SlimSession\Http\HttpConfig;
use Slim\App;
use Slim\Exception\HttpException;
use Slim\Routing\RouteContext;

final readonly class SessionResponseMiddleware implements MiddlewareInterface
{
    public function __construct(
        private Settings $settings,
        private HttpConfig $http,
        private JsonErrorRenderer $renderer,
        private App $app
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        $status = $response->getStatusCode();
        if (! in_array($status, [401, 403, 409, 503], true) || $response->getBody()->getSize() !== 0) {
            return $response;
        }

        $context = RouteContext::fromRequest($request);
        $apiPath = $this->app->getBasePath() . $this->settings->get('api_base_path');
        $path = $request->getUri()->getPath();
        if ($path === $apiPath || str_starts_with($path, $apiPath . '/')) {
            $error = new HttpException($request, $response->getReasonPhrase(), $status);
            $error->setTitle($status . ' ' . $response->getReasonPhrase());
            $response->getBody()->write(($this->renderer)($error, false));

            return $response->withHeader('Content-Type', 'application/json');
        }

        return $this->recoverPublicPage($request, $response, $context);
    }

    private function recoverPublicPage(
        ServerRequestInterface $request,
        ResponseInterface $response,
        RouteContext $context
    ): ResponseInterface {
        $route = $context->getRoute();
        if ($request->getMethod() !== 'GET' || $response->getStatusCode() !== 401
            || ! in_array($route?->getName(), ['home', 'login', 'apiInfo', 'blog'], true)) {
            return $response;
        }

        foreach ($response->getHeader('Set-Cookie') as $cookie) {
            if (str_starts_with($cookie, $this->http->cookieName . '=;')
                && str_contains($cookie, '; Max-Age=0;')) {
                $location = $context->getRouteParser()->urlFor($route->getName(), $route->getArguments());
                $query = $request->getUri()->getQuery();

                return $response->withStatus(302)
                    ->withHeader('Location', $location . ($query === '' ? '' : '?' . $query));
            }
        }

        return $response;
    }
}
