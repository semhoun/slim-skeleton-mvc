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
        if ($exception->getCode() === 0 || $exception->getCode() > 499) {
            // We are in debug mode, and is not app exception so we let tracy manage the exception
            throw $exception;
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
