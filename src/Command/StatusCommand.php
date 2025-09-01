<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:status',
    description: 'Returns current status of the application',
)]
class StatusCommand extends Command
{
    final protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $application = $this->getApplication();

        if (null === $application) {
            $io->error('Application not initialized.');

            return Command::FAILURE;
        }

        $io->title('Migrations status');

        // Check status for migrations.
        $command = new ArrayInput([
            'command' => 'doctrine:migrations:up-to-date',
        ]);
        $result = $application->doRun($command, $output);

        if (0 !== $result) {
            $io->info('Run doctrine:migrations:migrate to migrate to latest migration.');

            return Command::FAILURE;
        }

        $io->writeln('');
        $io->writeln('');
        $io->writeln('');
        $io->title('Templates status');

        // List status for templates.
        $command = new ArrayInput([
            'command' => 'app:templates:list',
            '--status' => true,
        ]);
        $result = $application->doRun($command, $output);

        if (0 !== $result) {
            $io->info('Run app:templates:install to install missing templates.');

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
