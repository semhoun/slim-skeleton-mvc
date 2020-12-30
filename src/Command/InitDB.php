<?php

namespace App\Command;

use DI\Container;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;


class InitDB extends Command
{
    private $settings;

    public function __construct(Container $container)
    {
        parent::__construct();
        $this->settings = $container->get('settings');
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
        $dbi = new \SQLite3($this->settings['doctrine']['connection']['path']);
        if (!$dbi) {
            $output->writeLn('Can\'t open sqlite3 database [' . $this->settings['doctrine']['connection']['path'] . ']');
            return;
        }
        $dbi->exec('BEGIN TRANSACTION;');

        $queries = [
"CREATE TABLE post (id int primary key not null, title char(100) default null, slug char(200) not null, content text not null);",
"INSERT INTO post VALUES(1,'First blog post','first-blog-post','This is sample blog post. If you see this content, doctrine is working fine.');",
"CREATE TABLE user (id int primary key not null, username char(30) not null, password char(60) not null, first_name char(50), last_name char(50), email char(50));",
"INSERT INTO user VALUES(1,'admin','\$2y\$10\$h2DgpuQvOWhpVmthACoKTuEVQHwHvcg5WjUekdvZx41hukm6LaUzy', 'Administator', 'THE', 'admin@admin.com');",
        ];

        foreach($queries as $query) {
            if (!$dbi->exec($query)) {
                $output->writeLn('Can\'t execute SQL query "' . $query . '": ' . $dbi->lastErrorMsg());
                $dbi->exec('ROLLBACK;');
                return;
            }
        }

        $dbi->exec('COMMIT;');

        $output->writeLn("Database structure created");
        $dbi->close();
    }
}
