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

    public function __invoke(\Throwable $exception, bool $displayErrorDetails): string
    {
        if ($this->settings->get('debug') && $exception->getCode() > 499) {
            // We are in debug mode, and is not app exeception so we let tracy manage the exception
            throw $exception;
        }

        if ($exception->getCode() === 404) {
            return $this->view->fetch('error/404.twig');
        }

        $title = '500 - ' .  $exception::class;
        if (is_a($exception, '\Slim\Exception\HttpException')) {
            $title = $exception->getTitle();
        }

        return $this->view->fetch('error/default.twig', [
            'title' => $title,
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
