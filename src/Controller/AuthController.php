<?php

declare(strict_types=1);

namespace App\Controller;

use Doctrine\ORM\EntityManager;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
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
        $session = $request->getAttribute('session');
        $session['logged'] = false;
        unset($session['uinfo']);
        return $response->withStatus(302)->withHeader('Location', '/');
    }

    private function handleLoginPost(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $uname = $data['uname'] ?? '';
        $pswd = $data['pswd'] ?? '';

        if ($uname === '' || $pswd === '') {
            return $this->renderLoginError($response, 'Empty value in login/password', $request);
        }

        $uinfo = $this->auth($uname, $pswd);
        if (! $uinfo instanceof \App\Entity\User) {
            return $this->renderLoginError($response, 'Invalid login/password', $request);
        }

        $this->createSession($request, $uinfo);

        return $response->withStatus(302)->withHeader('Location', '/');
    }

    private function renderLoginError(Response $response, string $message, Request $request): Response
    {
        return $this->twig->render($response, 'login.twig', [
            'flash' => [$message],
            'uinfo' => $request->getAttribute('uinfo'),
        ]);
    }

    private function createSession(Request $request, \App\Entity\User $user): void
    {
        $session = $request->getAttribute('session');
        $session['logged'] = true;
        $session['uinfo'] = [
            'id' => $user->getId(),
            'firstname' => $user->getFirstName(),
            'lastname' => $user->getLastName(),
            'email' => $user->getEmail(),
        ];
    }

    private function auth(string $uname, string $pswd): ?\App\Entity\User
    {
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
