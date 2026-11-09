<?php

declare(strict_types=1);

namespace App\Renderer;

use Monolog\Logger;
use Slim\Exception\HttpException;
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
        $code = $exception instanceof HttpException ? $exception->getCode() : 500;
        $safe = $displayErrorDetails || ($code >= 400 && $code < 500);
        $data = [
            'title' => $safe && $exception instanceof HttpException ?
                $exception->getTitle() : ($code === 500 ? '500 Internal Server Error' : $code . ' Server Error'),
            'code' => $code,
            'message' => $safe ? $exception->getMessage() : 'An internal server error occurred.',
        ];

        $details = [
            'debug' => $displayErrorDetails,
            'type' => $exception::class,
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ];

        if ($code !== 404) {
            $this->logger->error('[' . $code . '] ' . $exception->getMessage(), $details);
        }

        return json_encode(
            $displayErrorDetails ? array_merge($data, $details) : $data,
            JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR
        );
    }
}
