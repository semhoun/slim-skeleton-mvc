<?php

declare(strict_types=1);

namespace App\Renderer;

use App\Services\Settings;
use Slim\Interfaces\ErrorRendererInterface;
use Slim\Views\Twig;

final class HtmlErrorRenderer implements ErrorRendererInterface
{
    public function __construct(
        private Settings $settings,
        private Twig $view
    ) {
    }

    public function __invoke(
        \Throwable $exception,
        bool $displayErrorDetails
    ): string {
        if ($exception->getCode() === 404) {
            return $this->view->fetch('error/404.twig');
        }

        if ($exception->getCode() === 0 || $exception->getCode() > 499) {
            if ($this->settings->get('debug')) {
                // We are in debug mode, and is not app exception so we let tracy manage the exception
                throw $exception;
            }
            if ($displayErrorDetails) {
                return $this->view->fetch('error/default.twig', [
                    'title' => is_a($exception, '\Slim\Exception\HttpException') ?
                        $exception->getTitle() : '500 - ' .  $exception::class,
                    'debug' => $displayErrorDetails,
                    'type' => $exception::class,
                    'code' => $exception->getCode(),
                    'message' => $exception->getMessage(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    'trace' => $exception->getTraceAsString(),
                ]);
            }
        }

        return $this->view->fetch('error/default.twig', [
            'title' => is_a($exception, '\Slim\Exception\HttpException') ?
                        $exception->getTitle() : '500 - ' .  $exception::class,
            'debug' => $displayErrorDetails,
            'type' => $exception::class,
            'code' => $exception->getCode(),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
