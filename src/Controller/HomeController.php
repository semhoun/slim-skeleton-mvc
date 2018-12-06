<?php
namespace App\Controller;

use Slim\Http\Request;
use Slim\Http\Response;

final class HomeController extends BaseController
{
    public function index(Request $request, Response $response, $args)
    {
        $this->logger->info("Home page action dispatched");

        $this->flash->addMessage('info', 'Sample flash message');

        $this->view->render($response, 'home/index.twig');
        return $response;
    }

    public function login(Request $request, Response $response, $args)
    {
        $messages = $this->flash->getMessage('error');
        $this->view->render($response, 'home/login.twig', ['flash' => $messages]);
        return $response;
    }

    public function viewPost(Request $request, Response $response, $args)
    {
        $this->logger->info("View post using Doctrine with Slim 3");

        $messages = $this->flash->getMessage('info');

        try {
            $post = $this->em->find('App\Entity\Post', intval($args['id']));
        } catch (\Exception $e) {
            echo $e->getMessage();
            die;
        }

        $token = null;
        if ($this->token->logged()) $token = $this->token->data();

        $this->view->render($response, 'home/post.twig', ['post' => $post, 'flash' => $messages, 'token' => $token]);
        return $response;
    }
}
