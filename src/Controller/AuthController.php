<?php

namespace App\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class AuthController extends BaseController
{

    private function auth(string $uname, string $pswd)
    {
        $uinfo = $this->em->getRepository(\App\Entity\User::Class)->findOneByUsername($uname);
        if ($uinfo == null) return null;
        if (!password_verify($pswd, $uinfo->getPassword())) return null;

        return $uinfo;
    }

    public function login(Request $request, Response $response, array $args = []): Response
    {
        if ($request->getMethod() == 'POST') {
            $data = $request->getParsedBody();

            if (empty($data["uname"]) || empty($data["pswd"])) {
                $this->flash->addMessage('error', 'Empty value in login/password');
                return $response->withStatus(302)->withHeader('Location', '/member/login');
            }

            // Check the user username / pass
            $uinfo = $this->auth($data["uname"], $data['pswd']);
            if ($uinfo == null) {
                $this->flash->addMessage('error', 'Invalid login/password');
                return $response->withStatus(302)->withHeader('Location', '/member/login');
            }

            $_SESSION['logged'] = 'true';
            $_SESSION['uinfo'] = [
                'id' => $uinfo->getId(),
                'firstname' => $uinfo->getFirstName(),
                'lastname' => $uinfo->getLastName(),
                'email' => $uinfo->getEmail()
            ];

            $this->flash->addMessage('info', 'Logged');
            return $response->withStatus(302)->withHeader('Location', '/post/1');
        }
        else {
            return $this->view->render($response, 'login.twig', ['flash' => $messages]);
        }
    }

    public function logout(Request $request, Response $response, array $args = []): Response
    {
        $_SESSION['logged'] = 'false';
        unset($_SESSION['uinfo']);
        return $response->withStatus(302)->withHeader('Location', '/');
    }
}
