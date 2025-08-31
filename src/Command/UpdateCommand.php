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
    name: 'app:update',
    description: 'Check if important commands have been run. Use --force to run the commands.',
)]
class UpdateCommand extends Command
{
    protected function configure(): void
    {
        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Update all requirements. Otherwise, update command only checks requirements.');
    }

    final protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $force = $input->getOption('force');

        if ($force) {
            // Run migrations.
            $migrationsCommand = new ArrayInput([
                'command' => 'doctrine:migrations:migrate',
            ]);
            $migrationsCommand->setInteractive(false);
            $this->getApplication()->doRun($migrationsCommand, $output);

            // Install all templates.
            $migrationsCommand = new ArrayInput([
                'command' => 'app:templates:install',
                '--all' => true,
                '--update' => true,
            ]);
            $migrationsCommand->setInteractive(false);
            $this->getApplication()->doRun($migrationsCommand, $output);
        } else {
            $io->title('Migrations status');

            // Check status for migrations.
            $command = new ArrayInput([
                'command' => 'doctrine:migrations:up-to-date',
            ]);
            $result = $this->getApplication()->doRun($command, $output);

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
            $result = $this->getApplication()->doRun($command, $output);

            if (0 !== $result) {
                $io->info('Run app:templates:install to install missing templates.');

                return Command::FAILURE;
            }
        }

        return Command::SUCCESS;
    }
}
