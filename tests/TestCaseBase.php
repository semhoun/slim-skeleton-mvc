<?php



declare(strict_types=1);



namespace App\Tests;



use App\Services\Settings;

use DI\ContainerBuilder;

use PHPUnit\Framework\TestCase;

use Psr\Http\Message\ServerRequestInterface as Request;

use Slim\App;

use Slim\Psr7\Factory\ServerRequestFactory;



abstract class TestCaseBase extends TestCase

{

    protected App $app;



    protected function setUp(): void

    {

        $rootPath = Settings::getAppRoot();

        

        // Force debug to false for tests to avoid Tracy and detailed errors in output

        putenv('DEBUG_MODE=false');

        $settings = Settings::load();



        $containerBuilder = new ContainerBuilder();

        $containerBuilder->addDefinitions($rootPath . '/config/dependencies.php');

        $container = $containerBuilder->build();



        $this->app = \DI\Bridge\Slim\Bridge::create($container);



        $middleware = require $rootPath . '/config/middleware.php';

        $middleware($this->app);



        $routes = require $rootPath . '/config/routes.php';

        $routes($this->app);

    }



    protected function createRequest(

        string $method,

        string $path,

        array $headers = ['HTTP_ACCEPT' => 'application/json'],

        array $cookies = [],

        array $serverParams = []

    ): Request {

        $uri = (new ServerRequestFactory())->createResponse(200)->getUri()->withPath($path);

        return (new ServerRequestFactory())->createServerRequest($method, $uri, $serverParams);

    }

}

