<?php

declare(strict_types=1);

namespace Skeleton;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240829075026 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'User table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE user (id INTEGER PRIMARY KEY AUTOINCREMENT NOT null, ' .
            'username CHAR(30) NOT null, password VARCHAR(255) not null, ' .
            'first_name CHAR(50), last_name CHAR(50), email CHAR(50));'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE user');
    }
}
