<?php
declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class SessionMiddleware implements Middleware
{
	private $storage;

	public function __construct()
	{
        session_start();
		$this->storage = &$_SESSION;
	}

	public function &getStorage(): array
    {
        return $this->storage;
    }

    /**
     * {@inheritdoc}
     */
    public function process(Request $request, RequestHandler $handler): Response
    {
        if (!isset($this->storage['logged'])) {
            $this->storage['logged'] = false;
        }

        $request = $request->withAttribute('session', $this->storage);
        $request = $request->withAttribute('uinfo', array_key_exists('uinfo', $this->storage) ? $this->storage['uinfo'] : null);

        return $handler->handle($request);
    }
}
