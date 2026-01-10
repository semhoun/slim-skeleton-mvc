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
        if ($exception->getCode() === 404) {
            return $this->twig->fetch('error/404.twig', [
                'title' => is_a($exception, '\Slim\Exception\HttpException') ?
                    $exception->getTitle() : '500 - ' .  $exception::class,
                'code' => $exception->getCode(),
                'message' => $exception->getMessage(),
            ]);
        }

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

        $this->logger->error('[' . $exception->getCode() . '] ' . $exception->getMessage(), $details);

        if ($this->settings->get('debug')) {
            // We are in debug mode, and is not app exception so we let tracy manage the exception
            throw $exception;
        }

        return $this->twig->fetch('error/default.twig', $displayErrorDetails ? array_merge($data, $details) : $data);
    }
}
