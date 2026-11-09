<?php

declare(strict_types=1);

namespace App\Tests\Services;

use App\Console\CreateUserConsoleCommand;
use App\Entity\User;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\Migrations\Configuration\Connection\ExistingConnection;
use Doctrine\Migrations\Configuration\Migration\ConfigurationArray;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\MigratorConfiguration;
use Doctrine\Migrations\Version\Version;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Tester\CommandTester;

final class UserProvisioningTest extends TestCase
{
    private Connection $connection;
    private DependencyFactory $migrations;
    private CreateUserConsoleCommand $command;

    protected function setUp(): void
    {
        $this->connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
        $settings = require dirname(__DIR__, 2) . '/config/settings/database.php';
        $this->migrations = DependencyFactory::fromConnection(
            new ConfigurationArray($settings['doctrine']['migrations']),
            new ExistingConnection($this->connection)
        );
        $this->migrations->getMetadataStorage()->ensureInitialized();
    }

    protected function tearDown(): void
    {
        $this->connection->close();
    }

    public function testDiscoveryAndFreshInstallWithoutDefaultAccount(): void
    {
        $versions = array_map(
            static fn ($migration): string => (string) $migration->getVersion(),
            $this->migrations->getMigrationRepository()->getMigrations()->getItems()
        );
        self::assertSame([
            'Skeleton\\Version20240829075026',
            'Skeleton\\Version20240829075240',
            'Skeleton\\Version20261107152100',
        ], $versions);

        $this->migrateTo('Skeleton\\Version20261107152100');

        self::assertSame(0, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM user'));
        self::assertSame(2, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM post'));
        self::assertSame(3, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM db_version'));
    }

    public function testCleanupOnlyDeletesAdminWithDefaultPassword(): void
    {
        $this->migrateTo('Skeleton\\Version20240829075240');
        $customHash = password_hash('a-custom-password', PASSWORD_DEFAULT);
        foreach ([
            ['admin', password_hash('admin', PASSWORD_DEFAULT)],
            ['admin', password_hash('admin', PASSWORD_BCRYPT, ['cost' => 4])],
            ['admin', $customHash],
            ['another-user', password_hash('admin', PASSWORD_DEFAULT)],
            ['admin', 'invalid-hash'],
        ] as [$username, $password]) {
            $this->connection->insert('user', ['username' => $username, 'password' => $password]);
        }

        $this->migrateTo('Skeleton\\Version20261107152100');

        $users = $this->connection->fetchAllAssociative('SELECT username, password FROM user ORDER BY id');
        self::assertCount(3, $users);
        self::assertSame(['username' => 'admin', 'password' => $customHash], $users[0]);
        self::assertSame('another-user', $users[1]['username']);
        self::assertSame(['username' => 'admin', 'password' => 'invalid-hash'], $users[2]);
    }

    public function testFreshInstallCanBeSimulatedWithoutCreatingApplicationTables(): void
    {
        $plan = $this->migrations->getMigrationPlanCalculator()->getPlanUntilVersion(
            new Version('Skeleton\\Version20261107152100')
        );
        $results = $this->migrations->getMigrator()->migrate(
            $plan,
            (new MigratorConfiguration())->setDryRun(true)->setAllOrNothing(true)
        );
        self::assertCount(3, $results);
        self::assertFalse($this->connection->createSchemaManager()->tablesExist(['user']));
        self::assertFalse($this->connection->createSchemaManager()->tablesExist(['post']));
        self::assertSame(0, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM db_version'));
    }

    public function testCleanupSimulationPreservesExistingDefaultAccount(): void
    {
        $this->migrateTo('Skeleton\\Version20240829075240');
        $this->connection->insert('user', [
            'username' => 'admin',
            'password' => password_hash('admin', PASSWORD_BCRYPT, ['cost' => 4]),
        ]);
        $plan = $this->migrations->getMigrationPlanCalculator()->getPlanUntilVersion(
            new Version('Skeleton\\Version20261107152100')
        );
        $results = $this->migrations->getMigrator()->migrate(
            $plan,
            (new MigratorConfiguration())->setDryRun(true)->setAllOrNothing(true)
        );
        self::assertCount(1, $results);
        self::assertSame(1, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM user'));
        self::assertSame(2, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM db_version'));
    }

    public function testCreateUserHashesPasswordAndRejectsExistingUsername(): void
    {
        $tester = $this->commandTester();
        $password = 'secure-password-123';
        $tester->setInputs([$password, $password]);
        self::assertSame(Command::SUCCESS, $tester->execute(['username' => 'operator']));

        $user = $this->connection->fetchAssociative('SELECT username, password FROM user');
        self::assertSame('operator', $user['username']);
        self::assertNotSame($password, $user['password']);
        self::assertTrue(password_verify($password, $user['password']));
        self::assertFalse(password_needs_rehash($user['password'], PASSWORD_DEFAULT));
        self::assertStringNotContainsString($password, $tester->getDisplay());
        self::assertSame(Command::FAILURE, $tester->execute(['username' => 'operator']));
        self::assertSame(1, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM user'));
    }

    #[DataProvider('invalidInputs')]
    public function testInvalidInputsDoNotCreateAccounts(string $username, array $inputs): void
    {
        $tester = $this->commandTester();
        $tester->setInputs($inputs);
        self::assertSame(Command::INVALID, $tester->execute(['username' => $username]));
        self::assertSame(0, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM user'));
    }

    public static function invalidInputs(): array
    {
        return [
            'empty username' => ['', []],
            'blank username' => ['   ', []],
            'long username' => [str_repeat('x', 31), []],
            'short password' => ['operator', ['short']],
            'empty password' => ['operator', ['']],
            'long password' => ['operator', [str_repeat('x', 73)]],
            'confirmation mismatch' => ['operator', ['secure-password-123', 'different-password']],
        ];
    }

    public function testNonInteractiveEntryIsRefusedAndNoPasswordOptionExists(): void
    {
        $tester = $this->commandTester();
        self::assertFalse($this->command->getDefinition()->hasOption('password'));
        self::assertSame(Command::INVALID, $tester->execute(['username' => 'operator'], ['interactive' => false]));
        self::assertSame(0, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM user'));
    }

    public function testBothPasswordQuestionsForbidVisibleFallback(): void
    {
        $tester = $this->commandTester();
        $helper = $this->createMock(QuestionHelper::class);
        $helper->expects(self::exactly(2))->method('ask')->willReturnCallback(
            static function ($input, $output, Question $question): string {
                self::assertTrue($question->isHidden());
                self::assertFalse($question->isHiddenFallback());
                return 'secure-password-123';
            }
        );
        $this->command->getHelperSet()->set($helper, 'question');

        self::assertSame(Command::SUCCESS, $tester->execute(['username' => 'operator']));
    }

    public function testNullBytePasswordIsRejectedBeforeConfirmation(): void
    {
        $tester = $this->commandTester();
        $helper = $this->createMock(QuestionHelper::class);
        $helper->expects(self::once())->method('ask')->willReturn("a-long-password\0");
        $this->command->getHelperSet()->set($helper, 'question');
        self::assertSame(Command::INVALID, $tester->execute(['username' => 'operator']));
        self::assertSame(0, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM user'));
    }

    private function commandTester(): CommandTester
    {
        $this->migrateTo('Skeleton\\Version20261107152100');
        $entityManager = new EntityManager($this->connection, ORMSetup::createAttributeMetadataConfiguration(
            [dirname(__DIR__, 2) . '/src/Entity'],
            true
        ));
        self::assertSame(255, $entityManager->getClassMetadata(User::class)->getFieldMapping('password')->length);

        $this->command = new CreateUserConsoleCommand($entityManager);
        (new Application())->add($this->command);

        return new CommandTester($this->command);
    }

    private function migrateTo(string $version): void
    {
        $plan = $this->migrations->getMigrationPlanCalculator()->getPlanUntilVersion(new Version($version));
        $this->migrations->getMigrator()->migrate($plan, (new MigratorConfiguration())->setAllOrNothing(true));
    }
}
