<?php

declare(strict_types=1);

namespace App\Controller;

use App\Renderer\JsonRenderer;
use Doctrine\ORM\EntityManager;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final readonly class AdminController
{
    public function __construct(
        private Twig $twig,
        private JsonRenderer $jsonRenderer,
        private EntityManager $entityManager
    ) {
    }

    public function view(Request $request, Response $response): Response
    {
        return $this->twig->render($response, 'admin.twig');
    }

    public function databasesInfo(Request $request, Response $response): Response
    {
        return $this->jsonRenderer->json($response, [
            [ 'id' => 'Post', 'title' => 'Blog Post'],
            [ 'id' => 'User', 'title' => 'Users'],
        ]);
    }

    public function databaseExport(Request $request, Response $response, string $id): Response
    {
        $data = $this->entityManager->getRepository('\\App\\Entity\\' . $id)->adminExport();
        return $this->jsonRenderer->json($response, $data);
    }
}
