<?php
namespace App\Renderer;

use Psr\Container\ContainerInterface;
use Slim\Interfaces\ErrorRendererInterface;
use Slim\Views\Twig;

class HtmlErrorRenderer implements ErrorRendererInterface
{
    protected $view;

    public function __construct(ContainerInterface $c)
    {
        $settings = $c->get('settings');
        $this->view = new Twig(
            $settings['view']['template_path'],
            $settings['view']['twig']
        );
    }

    public function __invoke(\Throwable $exception, bool $displayErrorDetails): string
    {
        if ($exception->getCode() == 404) {
            return $this->view->fetch('error/404.twig');
        }
        return $this->view->fetch('error/default.twig', [
            'code' => $exception->getCode(),
            'message' => $exception->getMessage(),
            'debug' => $displayErrorDetails,
            'type' => get_class($exception),
            'file' => $exception->getFile(),
            'line' =>  $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
