<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
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
        $application->doRun($command, $output);

        $io->writeln('');
        $io->writeln('');
        $io->writeln('');
        $io->title('Templates status');

        // List status for templates.
        $command = new ArrayInput([
            'command' => 'app:templates:list',
            '--status' => true,
        ]);
        $application->doRun($command, $output);

        $io->writeln('');
        $io->writeln('');
        $io->writeln('');
        $io->title('Screen layout status');

        // List status for templates.
        $command = new ArrayInput([
            'command' => 'app:screen-layouts:list',
            '--status' => true,
        ]);
        $application->doRun($command, $output);

        $io->info('Run app:update to update migrations, templates and screen layouts.');

        return Command::SUCCESS;
    }
}
