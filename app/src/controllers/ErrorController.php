<?php
namespace App\Controller;

use Slim\Http\Response;

final class ErrorController extends BaseController
{
    public function E401($request, Response $response, $args)
    {
        $this->view->render($response, 'error/401.twig');
        return $response->withStatus(401);
    }

    public function E403($request, Response $response, $args)
    {
        $this->view->render($response, 'error/403.twig');
        return $response->withStatus(404);
    }

    public function E404($request, Response $response, $args)
    {
        $this->view->render($response, 'error/404.twig');
        return $response->withStatus(404);
    }
}
