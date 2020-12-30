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
                $this->flash->addMessage('info', 'Empty value in login/password');
                return $response->withStatus(302)->withHeader('Location', '/member/login');
            }

            // Check the user username / pass
            $uinfo = $this->auth($data["uname"], $data['pswd']);
            if ($uinfo == null) {
                $this->flash->addMessage('info', 'Invalid login/password');
                return $response->withStatus(302)->withHeader('Location', '/member/login');
            }

            $session = $request->getAttribute('session');
            $session['logged'] = true;
            $session['uinfo'] = [
                'id' => $uinfo->getId(),
                'firstname' => $uinfo->getFirstName(),
                'lastname' => $uinfo->getLastName(),
                'email' => $uinfo->getEmail()
            ];

            $this->flash->addMessage('info', 'Logged');
            return $response->withStatus(302)->withHeader('Location', '/');
        }
        return $this->view->render($response, 'login.twig', ['flash' => $this->flash->getMessage('info') , 'uinfo' => $request->getAttribute('uinfo')]);
    }

    public function logout(Request $request, Response $response, array $args = []): Response
    {
        $session = $request->getAttribute('session');
        $session['logged'] = false;
        unset($session['uinfo']);
        return $response->withStatus(302)->withHeader('Location', '/');
    }
}
