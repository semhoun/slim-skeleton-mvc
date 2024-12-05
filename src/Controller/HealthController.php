<?php

declare(strict_types=1);

namespace App\Controller;

use App\Renderer\JsonRenderer;
use App\Services\Health;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class HealthController
{
    public function __construct(
        private JsonRenderer $renderer,
        private Health $health,
    ) {
    }

    /**
     * Handles a health check request and returns a response indicating the service health.
     *
     * @param Request $request The incoming request to handle.
     * @param Response $response The response to be sent.
     *
     * @return Response Returns the response with the service health status.
     */
    public function health(Request $request, Response $response): Response
    {
        return $this->renderer->json($response, $this->health->status());
    }
}
