<?php

declare(strict_types=1);

namespace App\Console;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

#[AsCommand(name: 'app:user:create', description: 'Create an administrative account')]
final class CreateUserConsoleCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('username', InputArgument::REQUIRED, 'Username (maximum 30 characters)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $username = trim((string) $input->getArgument('username'));
        if ($username === '' || mb_strlen($username) > 30) {
            $output->writeln('<error>Username must contain between 1 and 30 characters.</error>');
            return Command::INVALID;
        }

        if ($this->entityManager->getRepository(User::class)->findOneBy(['username' => $username]) !== null) {
            $output->writeln('<error>Username already exists.</error>');
            return Command::FAILURE;
        }

        if (! $input->isInteractive()) {
            $output->writeln('<error>Interactive hidden password entry is required.</error>');
            return Command::INVALID;
        }

        $helper = $this->getHelper('question');
        $question = new Question('Password (at least 12 characters, maximum 72 bytes): ');
        $question->setHidden(true);
        $question->setHiddenFallback(false);

        $password = $helper->ask($input, $output, $question);

        if (! is_string($password)
            || mb_strlen($password) < 12
            || strlen($password) > 72
            || str_contains($password, "\0")
        ) {
            $output->writeln('<error>Password requires 12+ characters, at most 72 bytes and no null bytes.</error>');
            return Command::INVALID;
        }

        $confirmation = new Question('Confirm password: ');
        $confirmation->setHidden(true);
        $confirmation->setHiddenFallback(false);
        if ($helper->ask($input, $output, $confirmation) !== $password) {
            $output->writeln('<error>Passwords do not match.</error>');
            return Command::INVALID;
        }

        $this->entityManager->persist(new User($username, password_hash($password, PASSWORD_DEFAULT)));
        $this->entityManager->flush();

        $output->writeln('<info>Administrative account created.</info>');

        return Command::SUCCESS;
    }
}
