<?php
namespace App\Renderer;

use Psr\Container\ContainerInterface;
use Slim\Interfaces\ErrorRendererInterface;
use Slim\Views\Twig;

class HtmlErrorRenderer implements ErrorRendererInterface
{
    protected $view;
    protected $debug;

    public function __construct(ContainerInterface $container)
    {
        $settings = $container->get('settings');
        $this->debug = $settings['debug'];
        $this->view = Twig::create($settings['view']['template_path'], $settings['view']['twig']);
    }

    public function __invoke(\Throwable $exception, bool $displayErrorDetails): string
    {
        if ($this->debug && $exception->getCode() > 499) {
            // We are in debug mode, and is not app exeception so we let tracy manage the exception
            throw $exception;
        }

        if ($exception->getCode() == 404) {
            return $this->view->fetch('error/404.twig');
        }

        $title = '500 - ' .  get_class($exception);
        if (is_a($exception, '\Slim\Exception\HttpException')) {
            $title = $exception->getTitle();
        }

        return $this->view->fetch('error/default.twig', [
            'title' => $title,
            'debug' => $displayErrorDetails,
            'type' => get_class($exception),
            'code' => $exception->getCode(),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' =>  $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
