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
            . json_encode($data, JSON_PRETTY_PRINT | JSON_NUMERIC_CHECK)
            . '</pre></body></html>';

        $response->getBody()->write($body);

        return $response;
    }

    public function json(
        ResponseInterface $response,
        mixed $data = [],
    ): ResponseInterface {
        $response = $response->withHeader('Content-Type', 'application/json');

        $response->getBody()->write(
            (string) json_encode(
                $data,
                JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR
            )
        );

        return $response;
    }
}
