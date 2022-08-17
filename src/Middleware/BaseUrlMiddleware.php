<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Middleware.
 */
final class BaseUrlMiddleware implements MiddlewareInterface
{
    /**
     * The app base path.
     *
     * @var string
     */
    private $basePath;

    /**
     * The constructor.
     *
     * @param string $basePath The slim app basePath ($app->getBasePath())
     */
    public function __construct(string $basePath = '')
    {
        $this->basePath = $basePath;
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
        $request = $request->withAttribute('base_url', $this->getBaseUrl($request));

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
        
        if ($request->hasHeader('HTTP_X_FORWARDED_PROTO')) {
            $scheme = $request->getHeader('HTTP_X_FORWARDED_PROTO')[0];
        }

        if ($authority !== '' && strpos($basePath, '/') !== 0) {
            $basePath .= '/' . $basePath;
        }

        return ($scheme !== '' ? $scheme . ':' : '')
                         . ($authority ? '//' . $authority : '')
                         . rtrim($basePath, '/');
    }
}