<?php

declare(strict_types=1);

namespace App\Tests;

use PHPUnit\Framework\TestCase;
use RuntimeException;

final class EnvHelperTest extends TestCase
{
    protected function tearDown(): void
    {
        foreach (['SKELETON_TEST_VALUE', 'SKELETON_TEST_MISSING'] as $key) {
            unset($_ENV[$key]);
            putenv($key);
        }
        parent::tearDown();
    }

    public function testEnvReturnsValue(): void
    {
        putenv('SKELETON_TEST_VALUE=hello');
        self::assertSame('hello', env('SKELETON_TEST_VALUE'));
        $_ENV['SKELETON_TEST_VALUE'] = 'override';
        self::assertSame('override', env('SKELETON_TEST_VALUE'));
    }

    public function testMissingOptionalVariableUsesDefault(): void
    {
        self::assertNull(env('SKELETON_TEST_MISSING'));
        self::assertSame('default', env('SKELETON_TEST_MISSING', 'default'));
    }

    public function testMandatoryVariableThrows(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('SKELETON_TEST_MISSING is missing');
        env_required('SKELETON_TEST_MISSING');
    }

    public function testMandatoryVariablesCanComeFromEnvironment(): void
    {
        putenv('SKELETON_TEST_VALUE=present');
        env_required(['SKELETON_TEST_VALUE']);
        self::assertSame('present', env('SKELETON_TEST_VALUE'));
    }

    public function testBooleanValuesAreConverted(): void
    {
        $_ENV['SKELETON_TEST_VALUE'] = 'false';
        self::assertFalse(env('SKELETON_TEST_VALUE'));
        $_ENV['SKELETON_TEST_VALUE'] = 'true';
        self::assertTrue(env('SKELETON_TEST_VALUE'));
    }
}
