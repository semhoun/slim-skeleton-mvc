<?php



declare(strict_types=1);



namespace App\Tests\Controller;



use App\Tests\TestCaseBase;

use Slim\Psr7\Factory\ServerRequestFactory;



class HealthControllerTest extends TestCaseBase

{

    public function testHealthEndpoint(): void

    {

        $request = (new ServerRequestFactory())->createServerRequest('GET', '/health');

        $response = $this->app->handle($request);



        $this->assertEquals(200, $response->getStatusCode());

        $this->assertEquals('application/json', $response->getHeaderLine('Content-Type'));

        

        $payload = json_decode((string) $response->getBody(), true);

        $this->assertArrayHasKey('version', $payload);

        $this->assertArrayHasKey('date', $payload);

    }

}

