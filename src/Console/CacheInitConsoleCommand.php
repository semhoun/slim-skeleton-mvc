<?php

declare(strict_types=1);

namespace App\Console;

use App\Services\Settings;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'cache:init', description: 'Initialiaze cache, and doctrine proxy')]
// php ./console cache:clear

final class CacheInitConsoleCommand extends Command
{
    public function __construct(
        private readonly Settings $settings,
        private \Doctrine\ORM\EntityManager $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $proxyFactory = $this->entityManager->getProxyFactory();
        $proxyFactory->generateProxyClasses(
            $this->entityManager->getMetadataFactory()->getAllMetadata()
        );

        return Command::SUCCESS;
    }
}
