<?php

declare(strict_types=1);

namespace App\Renderer;

use App\Services\Settings;
use Monolog\Logger;
use Slim\Interfaces\ErrorRendererInterface;
use Slim\Views\Twig;
use Throwable;

final readonly class HtmlErrorRenderer implements ErrorRendererInterface
{
    public function __construct(
        private Twig $twig,
        private Settings $settings,
        private Logger $logger,
    ) {
    }

    public function __invoke(
        Throwable $exception,
        bool $displayErrorDetails
    ): string {
        $data = [
            'title' => is_a($exception, '\Slim\Exception\HttpException') ?
                $exception->getTitle() : '500 - ' .  $exception::class,
            'code' => $exception->getCode(),
            'message' => $exception->getMessage(),
        ];

        if ($exception->getCode() === 404) {
            return $this->twig->fetch('error/404.twig', $data);
        }

        $details = $this->getErrorDetails($exception, $displayErrorDetails);
        $this->logger->error('[' . $exception->getCode() . '] ' . $exception->getMessage(), $details);

        if ($this->settings->get('debug')) {
            throw $exception;
        }

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
