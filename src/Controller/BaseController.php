<?php
namespace App\Controller;

use Psr\Container\ContainerInterface;

abstract class BaseController
{
    protected $view;
    protected $logger;
    protected $flash;
    protected $em;  // Entities Manager

    public function __construct(ContainerInterface $c)
    {
        $this->view = $c->get('view');
        $this->logger = $c->get('logger');
        $this->flash = $c->get('flash');
        $this->em = $c->get('em');
    }
}
