<?php

declare(strict_types=1);

namespace Skeleton;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261107152100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove only admin accounts still using the default admin password';
    }

    public function up(Schema $schema): void
    {
        // A fresh dry-run does not execute preceding CREATE TABLE statements.
        if (! $this->sm->tablesExist(['user'])) {
            return;
        }

        foreach ($this->connection->fetchAllAssociative(
            'SELECT id, password FROM user WHERE username = ?',
            ['admin']
        ) as $user) {
            if (password_verify('admin', $user['password'])) {
                $this->addSql(
                    'DELETE FROM user WHERE id = ? AND username = ? AND password = ?',
                    [$user['id'], 'admin', $user['password']]
                );
            }
        }
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Default credentials must not be restored.');
    }
}
