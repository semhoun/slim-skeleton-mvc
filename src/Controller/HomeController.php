<?php
namespace App\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class HomeController extends BaseController
{
    public function index(Request $request, Response $response, array $args = []): Response
    {
        $this->logger->info("Home page action dispatched");

        $this->flash->addMessage('info', 'Sample flash message');

        $this->view->render($response, 'index.twig');
        return $response;
    }

    public function viewPost(Request $request, Response $response, array $args = []): Response
    {
        $this->logger->info("View post using Doctrine with Slim 4");

        $messages = $this->flash->getMessage('info');

        try {
            $post = $this->em->find('App\Entity\Post', intval($args['id']));
        } catch (\Exception $e) {
            echo $e->getMessage();
            die;
        }

        $uinfo = $request->getAttribute('uinfo');

        $this->view->render($response, 'post.twig', ['post' => $post, 'flash' => $messages, 'uinfo' => $uinfo]);
        return $response;
    }
}
