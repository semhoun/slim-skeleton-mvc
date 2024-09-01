<?php

declare(strict_types=1);

namespace App\Controller;

use Doctrine\ORM\EntityManager;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class BlogController
{
    public function __construct(
        private Twig $view,
        private EntityManager $entityManager
    ) {
    }

    /**
     * @param $id Id of post to view
     */
    public function view(Request $request, Response $response, int $id): Response
    {
        try {
            $post = $this->entityManager->getRepository(\App\Entity\Post::class)->find($id);
        } catch (\Exception $e) {
            throw new \Slim\Exception\HttpInternalServerErrorException($request, $e->getMessage());
        }

        return $this->view->render($response, 'blog.twig', [ 'post' => $post ]);
    }
}
