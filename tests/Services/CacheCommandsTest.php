<?php

declare(strict_types=1);

namespace App\Tests\Services;

use App\Console\CacheClearConsoleCommand;
use App\Console\GenerateProxies;
use App\Entity\Post;
use App\Entity\User;
use App\Services\Settings;
use DI\ContainerBuilder;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class CacheCommandsTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/slim-cache-test-' . bin2hex(random_bytes(8));
        mkdir($this->root, 0700);
        mkdir($this->root . '/cache', 0700);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->root . '/cache/locked')) {
            chmod($this->root . '/cache/locked', 0700);
        }
        (new CommandTester(new CacheClearConsoleCommand(new Settings(['cache_dir' => $this->root]))))->execute([]);
        rmdir($this->root);
        parent::tearDown();
    }

    public function testClearRemovesHiddenFilesWithoutFollowingSymlinks(): void
    {
        mkdir($this->root . '/outside', 0700);
        file_put_contents($this->root . '/outside/keep', 'preserve');
        file_put_contents($this->root . '/cache/.hidden', 'cache');
        mkdir($this->root . '/cache/nested', 0700);
        file_put_contents($this->root . '/cache/nested/item', 'cache');
        symlink($this->root . '/outside', $this->root . '/cache/link');
        $tester = $this->clearTester();
        self::assertSame(Command::SUCCESS, $tester->execute([]));
        self::assertSame([], iterator_to_array(new \FilesystemIterator($this->root . '/cache')));
        self::assertSame('preserve', file_get_contents($this->root . '/outside/keep'));
    }

    public function testClearReportsPermissionFailure(): void
    {
        mkdir($this->root . '/cache/locked', 0700);
        file_put_contents($this->root . '/cache/locked/item', 'cache');
        file_put_contents($this->root . '/cache/unlocked', 'cache');
        chmod($this->root . '/cache/locked', 0500);
        if (is_writable($this->root . '/cache/locked')) {
            self::markTestSkipped('Current user can bypass directory permissions.');
        }
        $tester = $this->clearTester();
        self::assertSame(Command::FAILURE, $tester->execute([]));
        self::assertStringContainsString('Cannot remove cache file:', $tester->getDisplay());
        self::assertFileDoesNotExist($this->root . '/cache/unlocked');
    }

    public function testProductionProxyGenerationSupportsBothEntities(): void
    {
        $builder = new ContainerBuilder();
        $builder->addDefinitions(Settings::getAppRoot() . '/config/dependencies.php');
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
        $builder->addDefinitions([
            Settings::class => new Settings([
                'debug' => false,
                'cache_dir' => $this->root . '/cache',
                'database' => ['doctrine' => ['entity_path' => [Settings::getAppRoot() . '/src/Entity']]],
            ]),
            Connection::class => $connection,
        ]);
        $entityManager = $builder->build()->get(EntityManager::class);
        $tester = new CommandTester(new GenerateProxies($entityManager));
        self::assertSame(Command::SUCCESS, $tester->execute([]));
        self::assertInstanceOf(Post::class, $entityManager->getReference(Post::class, 1));
        self::assertInstanceOf(User::class, $entityManager->getReference(User::class, 1));
        $connection->close();
    }

    private function clearTester(): CommandTester
    {
        return new CommandTester(new CacheClearConsoleCommand(new Settings(['cache_dir' => $this->root . '/cache'])));
    }
}
