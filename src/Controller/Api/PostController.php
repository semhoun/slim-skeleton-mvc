<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Renderer\JsonRenderer;
use Doctrine\ORM\EntityManager;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class PostController
{
    public function __construct(
        private JsonRenderer $jsonRenderer,
        private EntityManager $entityManager
    ) {
    }

    /**
     * @param $postId Id of post to view
     */
    public function get(Request $request, Response $response, int $postId): Response
    {
        try {
            $post = $this->entityManager->getRepository(\App\Entity\Post::class)->find($postId);
        } catch (\Exception $exception) {
            throw new \Slim\Exception\HttpInternalServerErrorException($request, $exception->getMessage());
        }

        // Using interface JsonSerializable to make simplify
        return $this->jsonRenderer->json($response, $post);
    }

    public function getAll(Request $request, Response $response): Response
    {
        try {
            $posts = $this->entityManager->getRepository(\App\Entity\Post::class)->findAll();
        } catch (\Exception $exception) {
            throw new \Slim\Exception\HttpInternalServerErrorException($request, $exception->getMessage());
        }

        // Using html output for debug
        if (preg_match('/html/', $request->getHeaderLine('Accept'))) {
            return $this->jsonRenderer->html($response, $posts);
        }

        return $this->jsonRenderer->json($response, $posts);
    }

    public function add(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        if (empty($data['slug']) || empty($data['content'])) {
            throw new \Slim\Exception\HttpBadRequestException($request, 'Mandatory param not setted');
        }

        $post = new \App\Entity\Post($data['content'], $data['title'] ?? null);

        try {
            $this->entityManager->persist($post);
            $this->entityManager->flush();
        } catch (\Exception $exception) {
            throw new \Slim\Exception\HttpInternalServerErrorException($request, $exception->getMessage());
        }

        // Using interface JsonSerializable to make simplify
        return $this->jsonRenderer->json($response);
    }

    public function delete(Request $request, Response $response, int $postId): Response
    {
        try {
            $post = $this->entityManager->getRepository(\App\Entity\Post::class)->find($postId);
            $this->entityManager->remove($post);
            $this->entityManager->flush();
        } catch (\Exception $exception) {
            throw new \Slim\Exception\HttpInternalServerErrorException($request, $exception->getMessage());
        }

        // Using interface JsonSerializable to make simplify
        return $this->jsonRenderer->json($response);
    }
}
