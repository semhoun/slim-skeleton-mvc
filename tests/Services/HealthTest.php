<?php



declare(strict_types=1);



namespace App\Tests\Services;



use App\Services\Health;

use App\Services\Settings;

use PHPUnit\Framework\TestCase;



class HealthTest extends TestCase

{

    public function testStatusReturnsExpectedKeys(): void

    {

        $settings = new Settings(['version' => '1.2.3']);



        $health = new Health($settings);

        $status = $health->status();



        $this->assertArrayHasKey('version', $status);

        $this->assertArrayHasKey('date', $status);

        $this->assertEquals('1.2.3', $status['version']);

        $this->assertIsString($status['date']);

    }

}

