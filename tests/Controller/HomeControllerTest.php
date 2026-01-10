<?php



declare(strict_types=1);



namespace App\Tests\Controller;



use App\Tests\TestCaseBase;

use Slim\Psr7\Factory\ServerRequestFactory;



class HomeControllerTest extends TestCaseBase

{

    public function testHomePage(): void

    {

        $request = (new ServerRequestFactory())->createServerRequest('GET', '/');

        $response = $this->app->handle($request);



        $this->assertEquals(200, $response->getStatusCode());

        $this->assertStringContainsString('<html', (string) $response->getBody());

    }



    public function testApiInfoPage(): void

    {

        $request = (new ServerRequestFactory())->createServerRequest('GET', '/api_info');

        $response = $this->app->handle($request);



        $this->assertEquals(200, $response->getStatusCode());

    }



    public function testNotFound(): void

    {

        $request = (new ServerRequestFactory())->createServerRequest('GET', '/not-found-page');

        $response = $this->app->handle($request);



        $this->assertEquals(404, $response->getStatusCode());

    }

}

