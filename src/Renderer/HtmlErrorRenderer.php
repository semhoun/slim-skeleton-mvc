<?php

declare(strict_types=1);

namespace App\Renderer;

use Monolog\Logger;
use Slim\Exception\HttpException;
use Slim\Interfaces\ErrorRendererInterface;
use Slim\Views\Twig;
use Throwable;

final readonly class HtmlErrorRenderer implements ErrorRendererInterface
{
    public function __construct(
        private Twig $twig,
        private Logger $logger,
    ) {
    }

    public function __invoke(
        Throwable $exception,
        bool $displayErrorDetails
    ): string {
        $code = $exception instanceof HttpException ? $exception->getCode() : 500;
        $safe = $displayErrorDetails || ($code >= 400 && $code < 500);
        $data = [
            'title' => $safe && $exception instanceof HttpException ?
                $exception->getTitle() : ($code === 500 ? '500 Internal Server Error' : $code . ' Server Error'),
            'code' => $code,
            'message' => $safe ? $exception->getMessage() : 'An internal server error occurred.',
        ];

        if ($code === 404) {
            return $this->twig->fetch('error/404.twig', $data);
        }

        $details = $this->getErrorDetails($exception, $displayErrorDetails);
        $this->logger->error('[' . $code . '] ' . $exception->getMessage(), $details);

        return $this->twig->fetch('error/default.twig', $displayErrorDetails ? array_merge($data, $details) : $data);
    }

    /**
     * @return array<string, string|int|bool|null>
     */
    private function getErrorDetails(Throwable $throwable, bool $displayErrorDetails): array
    {
        return [
            'debug' => $displayErrorDetails,
            'type' => $throwable::class,
            'file' => $throwable->getFile(),
            'line' => $throwable->getLine(),
            'trace' => $throwable->getTraceAsString(),
        ];
    }
}
