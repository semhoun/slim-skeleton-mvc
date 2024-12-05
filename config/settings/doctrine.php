<?php

declare(strict_types=1);

use App\Services\Settings;

return [
    'entity_path' => [Settings::getAppRoot() . '/src/Entity'],
    'connection' => [
        'driver' => 'pdo_sqlite',
        'path' => Settings::getAppRoot() . '/var/database.sqlite',
    ],
    'migrations' => [
        'table_storage' => [
            'table_name' => 'db_version',
            'version_column_name' => 'version',
            'version_column_length' => 1024,
            'executed_at_column_name' => 'executed_at',
            'execution_time_column_name' => 'execution_time',
        ],
        'migrations_paths' => [
            'Mvno' => Settings::getAppRoot() . '/migrations',
        ],
        'all_or_nothing' => true,
        'transactional' => true,
        'check_database_platform' => true,
        'organize_migrations' => 'none',
        'connection' => null,
        'em' => null,
        'custom_template' => Settings::getAppRoot()
            . '/migrations/doctrine_migrations_class.php.tpl',
    ],
];
