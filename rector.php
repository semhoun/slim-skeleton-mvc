<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromStrictConstructorRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/config',
    ])
    ->withPhpSets(php82: true)
    ->withRules([
        TypedPropertyFromStrictConstructorRector::class,
    ])
    ->withAttributesSets(doctrine: true);
