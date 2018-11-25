<?php

namespace App\Controller;

use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;

class AuthController
{
    protected $flash;
    protected $auth;

    public function __construct(Container $c)
    {
        $this->flash = $c->get('flash');
        $this->auth = $c->get('auth');
    }

    public function login(Request $request, Response $response, $args)
    {
        $uname = $request->getParam('uname');
        $pswd = $request->getParam('pswd');

        $res = $this->auth->login($response, $uname, $pswd);
        if ($res == null) {
            $this->flash->addMessage('error', 'Invalid in login/password');
            return $response->withStatus(302)->withHeader('Location', '/login');
        }

        $response = $res;
        $this->flash->addMessage('info', 'Logged');
        return $response->withStatus(302)->withHeader('Location', '/member/post/1');
    }

    public function logout(Request $request, Response $response, $args)
    {
        $response = $this->auth->logout($response);
        return $response->withStatus(302)->withHeader('Location', '/');
    }
}
