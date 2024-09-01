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
        $this->addSql('CREATE TABLE user (id INTEGER PRIMARY KEY AUTOINCREMENT NOT null, username CHAR(30) NOT null, password CHAR(60) not null, first_name CHAR(50), last_name CHAR(50), email CHAR(50));');
        $this->addSql("INSERT INTO user VALUES(1,'admin','" . password_hash('admin', PASSWORD_DEFAULT) . "', 'Administator', 'THE', 'admin@admin.com');");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE user');
    }
}
