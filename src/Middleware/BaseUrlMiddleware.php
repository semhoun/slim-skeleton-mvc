<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\App;
use Slim\Views\Twig;

/**
 * Middleware.
 */
final class BaseUrlMiddleware implements MiddlewareInterface
{
    /**
     * The app base path.
     */
    private string $basePath;

    public function __construct(
        App $app,
        private Twig $view
    ) {
        $this->basePath = $app->getBasePath();
    }

    /**
     * Invoke middleware.
     *
     * @param ServerRequestInterface $request The request
     * @param RequestHandlerInterface $handler The handler
     *
     * @return ResponseInterface The response
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $baseUrl = $this->getBaseUrl($request);
        $this->view->getEnvironment()->addGlobal('base_url', $baseUrl);
        $request = $request->withAttribute('base_url', $baseUrl);

        return $handler->handle($request);
    }

    /**
     * Return the fully qualified base URL.
     *
     * Note that this method never includes a trailing /
     *
     * @param ServerRequestInterface $request The request
     *
     * @return string The base url
     */
    public function getBaseUrl(ServerRequestInterface $request): string
    {
        $uri = $request->getUri();
        $scheme = $uri->getScheme();
        $authority = $uri->getAuthority();
        $basePath = $this->basePath;

        if ($authority !== '' && ! str_starts_with($basePath, '/')) {
            $basePath .= '/' . $basePath;
        }

        return ($scheme !== '' ? $scheme . ':' : '')
                         . ($authority ? '//' . $authority : '')
                         . rtrim($basePath, '/');
    }
}
