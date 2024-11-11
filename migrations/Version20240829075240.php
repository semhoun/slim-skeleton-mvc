<?php

declare(strict_types=1);

namespace Skeleton;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240829075240 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Post table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE post (id INTEGER PRIMARY KEY AUTOINCREMENT NOT null, title CHAR(100) default null, content TEXT NOT null);');
        $this->addSQL("INSERT INTO post VALUES(1,'First blog post','This is sample blog post. If you see this content, doctrine is working fine.');");
        $this->addSQL("INSERT INTO post VALUES(2,'Second blog post','This is the second blog post.');");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE post');
    }
}
