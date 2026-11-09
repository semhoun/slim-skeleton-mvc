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
     * @param $id Id of post to view
     */
    public function get(Request $request, Response $response, int $id): Response
    {
        try {
            $post = $this->entityManager->getRepository(\App\Entity\Post::class)->find($id);
        } catch (\Exception $exception) {
            throw new \Slim\Exception\HttpInternalServerErrorException($request, $exception->getMessage(), $exception);
        }

        if ($post === null) {
            throw new \Slim\Exception\HttpNotFoundException($request, 'Post not found');
        }

        // Using interface JsonSerializable to make simplify
        return $this->jsonRenderer->json($response, $post);
    }

    public function getAll(Request $request, Response $response): Response
    {
        try {
            $posts = $this->entityManager->getRepository(\App\Entity\Post::class)->findAll();
        } catch (\Exception $exception) {
            throw new \Slim\Exception\HttpInternalServerErrorException($request, $exception->getMessage(), $exception);
        }

        return $this->jsonRenderer->json($response, $posts);
    }

    public function add(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        if (! is_array($data)
            || ! isset($data['content'])
            || ! is_string($data['content'])
            || trim($data['content']) === ''
        ) {
            throw new \Slim\Exception\HttpBadRequestException($request, 'Content must be a non-empty string');
        }

        if (isset($data['title']) && ! is_string($data['title'])) {
            throw new \Slim\Exception\HttpBadRequestException($request, 'Title must be a string or null');
        }

        $post = new \App\Entity\Post($data['content'], $data['title'] ?? null);

        try {
            $this->entityManager->persist($post);
            $this->entityManager->flush();
        } catch (\Exception $exception) {
            throw new \Slim\Exception\HttpInternalServerErrorException($request, $exception->getMessage(), $exception);
        }

        // Using interface JsonSerializable to make simplify
        return $this->jsonRenderer->json($response);
    }

    public function delete(Request $request, Response $response, int $id): Response
    {
        try {
            $post = $this->entityManager->getRepository(\App\Entity\Post::class)->find($id);
        } catch (\Exception $exception) {
            throw new \Slim\Exception\HttpInternalServerErrorException($request, $exception->getMessage(), $exception);
        }

        if ($post === null) {
            throw new \Slim\Exception\HttpNotFoundException($request, 'Post not found');
        }

        try {
            $this->entityManager->remove($post);
            $this->entityManager->flush();
        } catch (\Exception $exception) {
            throw new \Slim\Exception\HttpInternalServerErrorException($request, $exception->getMessage(), $exception);
        }

        // Using interface JsonSerializable to make simplify
        return $this->jsonRenderer->json($response);
    }
}
