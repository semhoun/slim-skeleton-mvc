<?php
namespace App\Controller;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

abstract class BaseController
{
    protected $view;
    protected $logger;
    protected $flash;
    protected $em;  // Entities Manager

    public function __construct(ContainerInterface $c)
    {
        $this->view = $c->get('view');
        $this->logger = $c->get('logger');
        $this->flash = $c->get('flash');
        $this->em = $c->get('em');
    }

    protected function render(Request $request, Response $response, string $template, array $params = []): Response
    {
        $params['flash'] = $this->flash->getMessage('info');
        $params['uinfo'] = $request->getAttribute('uinfo');

        return $this->view->render($response, $template, $params);
    }
}
