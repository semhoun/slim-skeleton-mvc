<?php

declare(strict_types=1);

namespace App\Renderer;

use Monolog\Logger;
use Slim\Interfaces\ErrorRendererInterface;
use Throwable;

final readonly class JsonErrorRenderer implements ErrorRendererInterface
{
    public function __construct(
        private Logger $logger,
    ) {
    }

    public function __invoke(
        Throwable $exception,
        bool $displayErrorDetails,
    ): string {
        $data = [
            'title' => is_a($exception, '\Slim\Exception\HttpException') ?
                $exception->getTitle() : '500 - ' .  $exception::class,
            'code' => $exception->getCode(),
            'message' => $exception->getMessage(),
        ];

        $details = [
            'debug' => $displayErrorDetails,
            'type' => $exception::class,
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ];

        if ($exception->getCode() === 404) {
            return json_encode($data) ?? [];
        }

        $this->logger->error('[' . $exception->getCode() . '] ' . $exception->getMessage(), $details);

        return json_encode($displayErrorDetails ? array_merge($data, $details) : $data) ?? [];
    }
}
