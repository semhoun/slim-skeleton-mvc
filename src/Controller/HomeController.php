<?php
namespace App\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class HomeController extends BaseController
{
    public function index(Request $request, Response $response, array $args = []): Response
    {
        $this->logger->info("Home page action dispatched");

        return $this->render($request, $response, 'index.twig');
    }

    public function viewPost(Request $request, Response $response, array $args = []): Response
    {
        $this->logger->info("View post using Doctrine with Slim 4");

        try {
            $post = $this->em->find('App\Entity\Post', intval($args['id']));
        } catch (\Exception $e) {
            throw new \Slim\Exception\HttpInternalServerErrorException($request, $e->getMessage());
        }

        return $this->render($request, $response, 'post.twig', ['post' => $post]);
    }
}
