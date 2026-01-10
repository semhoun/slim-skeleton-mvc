<?php

declare(strict_types=1);

namespace App\Tests;

use PHPUnit\Framework\TestCase;
use RuntimeException;

class EnvHelperTest extends TestCase
{
    public function testEnvReturnsValue(): void
    {
        putenv('TEST_VAR=hello');
        $_ENV['TEST_VAR'] = 'hello';
        $this->assertEquals('hello', env('TEST_VAR'));
    }

    public function testEnvReturnsDefaultValue(): void
    {
        $this->assertEquals('default', env('NON_EXISTENT_VAR', 'default'));
    }

    public function testEnvThrowsExceptionForMandatoryVar(): void
    {
        // On simule l'existence d'un fichier .env pour que $dotenv soit initialisé dans le helper
        $appRoot = dirname(__DIR__);
        if (!file_exists($appRoot . '/.env')) {
            file_put_contents($appRoot . '/.env', "APP_ENV=test\n");
            $created = true;
        } else {
            $created = false;
        }

        try {
            $this->expectException(RuntimeException::class);
            // On s'attend à un message d'erreur de phpdotenv indiquant que la variable est manquante
            $this->expectExceptionMessage('One or more environment variables failed assertions: MANDATORY_VAR is missing.');
            
            env('MANDATORY_VAR');
        } finally {
            if ($created) {
                unlink($appRoot . '/.env');
            }
        }
    }

    public function testEnvRequiredValidatesMultipleVars(): void
    {
        putenv('VAR1=val1');
        putenv('VAR2=val2');
        $_ENV['VAR1'] = 'val1';
        $_ENV['VAR2'] = 'val2';

        env_required(['VAR1', 'VAR2']);
        $this->addToAssertionCount(1);
    }

    public function testEnvRequiredThrowsExceptionWhenOneIsMissing(): void
    {
        putenv('VAR1=val1');
        $_ENV['VAR1'] = 'val1';
        unset($_ENV['MISSING_VAR']);
        putenv('MISSING_VAR'); // unset in putenv

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('MISSING_VAR is missing');

        env_required(['VAR1', 'MISSING_VAR']);
    }
}
