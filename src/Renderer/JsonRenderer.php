<?php

declare(strict_types=1);

namespace App\Renderer;

use Psr\Http\Message\ResponseInterface;

final class JsonRenderer
{
    public function html(
        ResponseInterface $response,
        mixed $data = null,
    ): ResponseInterface {
        $body = '<html><body><pre>'
            . htmlspecialchars(
                json_encode($data, JSON_PRETTY_PRINT | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR),
                ENT_QUOTES | ENT_SUBSTITUTE,
                'UTF-8'
            )
            . '</pre></body></html>';

        $response->getBody()->write($body);

        return $response->withHeader('Content-Type', 'text/html; charset=UTF-8');
    }

    public function json(
        ResponseInterface $response,
        mixed $data = [],
    ): ResponseInterface {
        $response = $response->withHeader('Content-Type', 'application/json');

        $response->getBody()->write(
            json_encode(
                $data,
                JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR
            )
        );

        return $response;
    }
}
