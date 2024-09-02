<?php

declare(strict_types=1);

namespace App\Console;

use App\Services\Settings;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'cache:clear', description: 'Clear all caches')]
final class CacheClearConsoleCommand extends Command
{
    public function __construct(
        private readonly Settings $settings
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $cacheDirs = [$this->settings->get('cache_dir')];
        foreach ($cacheDirs as $cacheDir) {
            if (! file_exists($cacheDir)) {
                continue;
            }

            $this->removeDirectoryContent($cacheDir);
        }

        return Command::SUCCESS;
    }

    private function removeDirectoryContent(string $path): void
    {
        $files = glob($path . '/*');
        if ($files === false) {
            return;
        }

        foreach ($files as $file) {
            is_dir($file) ? $this->removeDirectory($file) : unlink($file);
        }
    }

    private function removeDirectory(string $path): void
    {
        $files = glob($path . '/*');
        if ($files === false) {
            return;
        }

        foreach ($files as $file) {
            is_dir($file) ? $this->removeDirectory($file) : unlink($file);
        }
        rmdir($path);
    }
}
