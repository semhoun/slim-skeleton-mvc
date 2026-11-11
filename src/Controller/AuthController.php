<?php

declare(strict_types=1);

namespace App\Controller;

use Doctrine\ORM\EntityManager;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Semhoun\SlimSession\Http\SessionGuard;
use Semhoun\SlimSession\SessionContext;
use Semhoun\SlimSession\SessionState;
use Slim\Exception\HttpBadRequestException;
use Slim\Views\Twig;

final readonly class AuthController
{
    public function __construct(
        private Twig $twig,
        private EntityManager $entityManager
    ) {
    }

    public function login(Request $request, Response $response): Response
    {
        if ($request->getMethod() !== 'POST') {
            return $this->twig->render($response, 'login.twig', ['uinfo' => $request->getAttribute('uinfo')]);
        }

        return $this->handleLoginPost($request, $response);
    }

    public function logout(Request $request, Response $response): Response
    {
        $session = SessionGuard::context($request);
        if ($session->state() === SessionState::Open) {
            $session->destroy();
        }

        return $response->withStatus(302)->withHeader('Location', '/');
    }

    private function handleLoginPost(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        if (! is_array($data)) {
            throw new HttpBadRequestException($request, 'Expected a login form or JSON object.');
        }

        $uname = $data['uname'] ?? '';
        $pswd = $data['pswd'] ?? '';

        if (! is_string($uname)
            || ! is_string($pswd)
            || mb_strlen($uname) > 30
            || strlen($pswd) > 72
            || str_contains($pswd, "\0")
        ) {
            throw new HttpBadRequestException($request, 'Invalid login/password format.');
        }

        if ($uname === '' || $pswd === '') {
            return $this->renderLoginError($response, 'Empty value in login/password', $request);
        }

        $uinfo = $this->auth($uname, $pswd);
        if (! $uinfo instanceof \App\Entity\User) {
            return $this->renderLoginError($response, 'Invalid login/password', $request);
        }

        $this->createSession($uinfo, SessionGuard::context($request));

        return $response->withStatus(302)->withHeader('Location', '/');
    }

    private function renderLoginError(Response $response, string $message, Request $request): Response
    {
        return $this->twig->render($response, 'login.twig', [
            'flash' => [$message],
            'uinfo' => $request->getAttribute('uinfo'),
        ]);
    }

    private function createSession(\App\Entity\User $user, SessionContext $session): void
    {
        $session->startNew([
            'logged' => true,
            'uinfo' => [
                'id' => $user->getId(),
                'firstname' => $user->getFirstName(),
                'lastname' => $user->getLastName(),
                'email' => $user->getEmail(),
            ],
        ]);
    }

    private function auth(string $uname, string $pswd): ?\App\Entity\User
    {
        // Refuse the historical seed credentials even before cleanup is migrated.
        if ($uname === 'admin' && $pswd === 'admin') {
            return null;
        }

        $uinfo = $this->entityManager->getRepository(\App\Entity\User::class)->findOneByUsername($uname);
        if ($uinfo === null) {
            return null;
        }

        if (! password_verify($pswd, (string) $uinfo->getPassword())) {
            return null;
        }

        return $uinfo;
    }
}
