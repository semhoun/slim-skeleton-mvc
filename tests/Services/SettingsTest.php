<?php



declare(strict_types=1);



namespace App\Tests\Services;



use App\Services\Settings;

use PHPUnit\Framework\TestCase;

use RuntimeException;



class SettingsTest extends TestCase

{

    public function testGetReturnsValue(): void

    {

        $settings = new Settings(['key' => 'value']);

        $this->assertEquals('value', $settings->get('key'));

    }



    public function testGetReturnsNestedValue(): void

    {

        $settings = new Settings([

            'db' => [

                'host' => 'localhost'

            ]

        ]);

        $this->assertEquals('localhost', $settings->get('db.host'));

    }



    public function testGetThrowsExceptionOnInvalidKey(): void

    {

        $settings = new Settings(['key' => 'value']);

        

        $this->expectException(RuntimeException::class);

        $this->expectExceptionMessage('Trying to fetch invalid setting "invalid"');

        

        $settings->get('invalid');

    }



    public function testGetAppRoot(): void

    {

        $root = Settings::getAppRoot();

        $this->assertDirectoryExists($root);

        $this->assertFileExists($root . DIRECTORY_SEPARATOR . 'composer.json');

    }

}

