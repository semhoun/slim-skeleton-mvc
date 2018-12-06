<?php

namespace App\Lib;

use Slim\Container;
use Slim\Http\Response;
use App\Entity\User;
use App\Entity\Acl;
use Firebase\JWT\JWT;
use Ramsey\Uuid\Uuid;
use Dflydev\FigCookies\FigResponseCookies;
use Dflydev\FigCookies\SetCookie;

class JwtAuth
{
	protected $container;
    protected $logger;
    protected $em;
    protected $settings;

    public function __construct(Container $c)
    {
        $this->container = $c;
        $this->logger = $c->get('logger');
        $this->em = $c->get('em');
        $this->settings = $c->get('settings')['jwt'];
    }

    /**
     * PreCheck to renew jwt if needed
     */
    public function precheck(Response $response, $arguments)
    {
        $token = $this->container->get('token');
        if (!$token->logged()) return $response;
        $token = $token->decoded();

        $now = \time();
        if (($token['exp'] - $now) > ($this->settings['validity'] - $this->settings['refresh'])) return $response;
        $token['iat'] = $now;
        $token['exp'] = $now + $this->settings['validity'];

        $jwt = JWT::encode($token, $this->settings['secret'], $this->settings['algorithm']);

        // ReSet the cookie
        return FigResponseCookies::set($response, SetCookie::create('token')
                                       ->withValue($jwt)
                                       ->withPath('/')
                                       ->withDomain($_SERVER['SERVER_NAME'])
                                       ->withExpires($token['exp'])
        );
    }

    /**
     * Create the tokens for an user
     */
    private function createJwtToken(User $uinfo) {
        // Create user information
        $now = \time();
        $token = [
            'data' => [
                'username' => $uinfo->getUsername(),
                'firstname' => $uinfo->getFirstName(),
                'lastname' => $uinfo->getLastName(),
                'email' => $uinfo->getEmail(),
            ],
            'sub' => $uinfo->getId(),
            'iat' => $now,
            'exp' => $now + $this->settings['validity'],
            'jti' => Uuid::uuid4(), 
            'acl' => []
        ];

        // Get the ACL
        $acls = $this->em->getRepository(Acl::Class)->findByUserId($uinfo->getId());
        foreach ($acls as $acl) {
            $token['acl'][] = $acl->getAuth();
        }

        // Create the jwt token
        $jwt = JWT::encode($token, $this->settings['secret'], $this->settings['algorithm']);

        return ['token' => $token, 'jwt' => $jwt];
    }

    /**
     * Login user, and set cookies as needed
     * return null, otherwise the response
     */
    public function login(Response $response, string $username, string $password)
    {
        // Check the user username / pass
        $uinfo = $this->em->getRepository(User::Class)->findOneByUsername($username);
        if ($uinfo == null) return null;
        if (!password_verify($password, $uinfo->getPassword())) return null;

        $data = $this->createJwtToken($uinfo);

        // Add to token to container
        $this->container->get('token')->populate($data['token']);

        // Set the cookie
        $response = FigResponseCookies::set($response, SetCookie::create('token')
                                            ->withValue($data['jwt'])
                                            ->withPath('/')
                                            ->withDomain($_SERVER['SERVER_NAME'])
                                            ->withExpires($data['token']['exp'])
        );

        $this->logger->info($username . ' successfully logged, jti[' . $data['token']['jti'] . ']');

        return $response;
    }

    /**
     * Logout user and set cookies as needed
     */
    public function logout(Response $response)
    {
        $token = $this->container->get('token');
        $this->logger->info($token->data()['username'] . ' loggout, jti[' . $token->jti() . ']');
        $this->container->get('token')->clear();

        // Set the cookie
        return $response = FigResponseCookies::expire($response, 'token');
    }
}
