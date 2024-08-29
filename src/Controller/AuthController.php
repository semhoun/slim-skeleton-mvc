<?php

declare(strict_types=1);

namespace App\Controller;

use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class AuthController
{
    public function __construct(
        private Logger $logger,
        private Twig $view,
        private EntityManager $entityManager
    ) {
    }

    public function login(Request $request, Response $response): Response
    {
        if ($request->getMethod() === 'POST') {
            $data = $request->getParsedBody();

            if (empty($data['uname']) || empty($data['pswd'])) {
                return $this->view->render($response, 'login.twig', ['flash' => ['Empty value in login/password'], 'uinfo' => $request->getAttribute('uinfo')]);
            }

            // Check the user username / pass
            $uinfo = $this->auth($data['uname'], $data['pswd']);
            if ($uinfo === null) {
                return $this->view->render($response, 'login.twig', ['flash' => ['Invalid login/password'], 'uinfo' => $request->getAttribute('uinfo')]);
            }

            $session = $request->getAttribute('session');
            $session['logged'] = true;
            $session['uinfo'] = [
                'id' => $uinfo->getId(),
                'firstname' => $uinfo->getFirstName(),
                'lastname' => $uinfo->getLastName(),
                'email' => $uinfo->getEmail(),
            ];

            return $response->withStatus(302)->withHeader('Location', '/');
        }
        return $this->view->render($response, 'login.twig', ['uinfo' => $request->getAttribute('uinfo')]);
    }

    public function logout(Request $request, Response $response): Response
    {
        $session = $request->getAttribute('session');
        $session['logged'] = false;
        unset($session['uinfo']);
        return $response->withStatus(302)->withHeader('Location', '/');
    }

    private function auth(string $uname, string $pswd): ?\App\Entity\User
    {
        $uinfo = $this->entityManager->getRepository(\App\Entity\User::class)->findOneByUsername($uname);
        if ($uinfo === null) {
            return null;
        }
        if (! password_verify($pswd, $uinfo->getPassword())) {
            return null;
        }

        return $uinfo;
    }
}
