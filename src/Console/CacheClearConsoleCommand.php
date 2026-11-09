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
        $cacheDir = $this->settings->get('cache_dir');
        if (! is_dir($cacheDir)) {
            return Command::SUCCESS;
        }

        try {
            $this->removeDirectoryContent($cacheDir);
        } catch (\RuntimeException $runtimeException) {
            $output->writeln('<error>' . $runtimeException->getMessage() . '</error>');
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function removeDirectoryContent(string $path): void
    {
        $errors = [];
        foreach (new \FilesystemIterator($path, \FilesystemIterator::SKIP_DOTS) as $file) {
            try {
                $filename = $file->getPathname();
                if ($file->isDir() && ! $file->isLink()) {
                    $this->removeDirectoryContent($filename);
                    if (! @rmdir($filename)) {
                        throw new \RuntimeException('Cannot remove cache directory: ' . $filename);
                    }
                } elseif (! @unlink($filename)) {
                    throw new \RuntimeException('Cannot remove cache file: ' . $filename);
                }
            } catch (\RuntimeException $runtimeException) {
                $errors[] = $runtimeException->getMessage();
            }
        }

        if ($errors !== []) {
            throw new \RuntimeException(implode(PHP_EOL, $errors));
        }
    }
}
