<?php

declare(strict_types=1);

namespace App\Controller;

use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class HomeController
{
    public function __construct(
        private Logger $logger,
        private Twig $view,
        private EntityManager $entityManager
    ) {
    }

    public function index(Request $request, Response $response): Response
    {
        $this->logger->info('Home page action dispatched');

        return $this->view->render($response, 'index.twig');
    }

    public function apiInfo(Request $request, Response $response): Response
    {
        return $this->view->render($response, 'api.twig');
    }

    public function error(Request $request, Response $response): Response
    {
        $this->logger->info('Error log');

        throw new \Slim\Exception\HttpInternalServerErrorException($request, 'Try error handler');
    }
}
