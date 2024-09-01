<?php

declare(strict_types=1);

namespace App\Renderer;

use Slim\Interfaces\ErrorRendererInterface;

final class JsonErrorRenderer implements ErrorRendererInterface
{
    public function __invoke(
        \Throwable $exception,
        bool $displayErrorDetails
    ): string {
        if (is_a($exception, '\Slim\Exception\HttpException')) {
            return json_encode([
                'message' => $exception->getDescription(),
            ]);
        }

        if (($exception->getCode() >= 400 && $exception->getCode() <= 499) ||
                ! $displayErrorDetails) {
            return json_encode([
                'message' => $exception->getMessage(),
            ]);
        }

        return json_encode([
            'title' => $exception::class,
            'type' => $exception::class,
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ]);
    }
}
