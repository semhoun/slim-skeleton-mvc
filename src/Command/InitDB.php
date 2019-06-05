<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Slim\Container;

class InitDB extends Command
{
    private $settings;

    public function __construct(Container $c)
    {
        parent::__construct();
        $this->settings = $c->get('settings');
    }

    protected function configure()
    {
        $this
            // the name of the command (the part after "bin/console")
            ->setName('app:init-db')

            // the short description shown while running "php bin/console list"
            ->setDescription('Initialize database')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('Create database structe and add initial data');
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
		$db = new \SQLite3($this->settings['doctrine']['connection']['path']);
        if (!$db) {
            $output->writeLn('Can\'t open sqlite3 database [' . $this->settings['doctrine']['connection']['path'] . ']');
            return;
        }
        $db->exec('BEGIN TRANSACTION;');

        $queries = [
"CREATE TABLE post (id int primary key not null, title char(100) default null, slug char(200) not null, content text not null);",
"INSERT INTO post VALUES(1,'First blog post','first-blog-post','This is sample blog post. If you see this content, doctrine is working fine.');",
"CREATE TABLE user (id int primary key not null, username char(30) not null, password char(60) not null, first_name char(50), last_name char(50), email char(50));",
"INSERT INTO user VALUES(1,'admin','\$2y\$10\$h2DgpuQvOWhpVmthACoKTuEVQHwHvcg5WjUekdvZx41hukm6LaUzy','Administator', 'THE', 'admin@admin.com');",
"CREATE TABLE acl (user_id int not null, auth char(30) not null, PRIMARY KEY(user_id, auth),  FOREIGN KEY(user_id) REFERENCES user(id));",
"INSERT INTO acl VALUES(1, 'read');",
"INSERT INTO acl VALUES(1, 'write');",
"INSERT INTO acl VALUES(1, 'delete');"
        ];

        foreach($queries as $query) {
            if (!$db->exec($query)) {
                $output->writeLn('Can\'t execute SQL query "' . $query . '": ' . $db->lastErrorMsg());
                $db->exec('ROLLBACK;');
                return;
            }
        }

		$db->exec('COMMIT;');

        $output->writeLn("Database structure created");
        $db->close();
    }
}
