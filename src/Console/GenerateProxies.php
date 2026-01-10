<?php

declare(strict_types=1);

namespace App\Console;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:generate-proxies', description: 'Generate Proxies')]
final class GenerateProxies extends Command
{
    public function __construct(
        private readonly EntityManager $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Récupère toutes les metadata
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();

        if ($metadata === []) {
            $output->writeln('⚠️  Aucune entité détectée, rien à générer.');
            return Command::SUCCESS;
        }

        // Crée le dossier des proxys si nécessaire
        $proxyDir = $this->entityManager->getConfiguration()->getProxyDir();
        if (! is_dir($proxyDir)) {
            mkdir($proxyDir, 0775, true);
        }

        // Génère les proxys
        $this->entityManager->getProxyFactory()->generateProxyClasses($metadata);

        $output->writeln(sprintf('✅ Proxys générés dans : <info>%s</info>', $proxyDir));

        return Command::SUCCESS;
    }
}
