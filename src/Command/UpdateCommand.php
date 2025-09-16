<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\ScreenLayoutService;
use App\Service\TemplateService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:update',
    description: 'Run required updates.',
)]
class UpdateCommand extends Command
{
    public function __construct(
        private readonly TemplateService $templateService,
        private readonly ScreenLayoutService $screenLayoutService,
        ?string $name = null
    ) {
        parent::__construct($name);
    }

    final protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $isInteractive = $input->isInteractive();

        $application = $this->getApplication();

        if (null === $application) {
            $io->error('Application not initialized.');

            return Command::FAILURE;
        }

        $command = new ArrayInput([
            'command' => 'doctrine:migrations:migrate',
        ]);
        $command->setInteractive($isInteractive);
        $result = $application->doRun($command, $output);

        if (0 !== $result) {
            $io->info('Update aborted. Migrations need to run for the system to work. Run doctrine:migrations:migrate or rerun app:update to migrate.');

            return Command::FAILURE;
        }

        $allTemplates = $this->templateService->getAllTemplates();
        $installedTemplates = array_filter($allTemplates, fn ($entry): bool => $entry->installed);

        // If no installed templates, we assume that this is a new installation and offer to install all templates.
        if ($isInteractive && 0 === count($installedTemplates)) {
            $question = new ConfirmationQuestion('No templates are installed. Install all '.count($allTemplates).'?');
            $installAll = $io->askQuestion($question);

            if ($installAll) {
                $io->info('Installing all templates...');
                $command = new ArrayInput([
                    'command' => 'app:templates:install',
                    '--all' => true,
                ]);
                $application->doRun($command, $output);
            }
        } else {
            $io->info('Updating existing template...');
            $command = new ArrayInput([
                'command' => 'app:templates:update',
            ]);
            $application->doRun($command, $output);
        }

        $allScreenLayouts = $this->screenLayoutService->getAllScreenLayouts();
        $installedScreenLayouts = array_filter($allScreenLayouts, fn ($entry): bool => $entry->installed);

        // If no installed screen layouts, we assume that this is a new installation and offer to install all screen layouts.
        if ($isInteractive && 0 === count($installedScreenLayouts)) {
            $question = new ConfirmationQuestion('No screen layouts are installed. Install all '.count($allScreenLayouts).'?');
            $installAll = $io->askQuestion($question);

            if ($installAll) {
                $io->info('Installing all screen layouts...');
                $command = new ArrayInput([
                    'command' => 'app:screen-layouts:install',
                    '--all' => true,
                ]);
                $application->doRun($command, $output);
            }
        } else {
            $io->info('Updating existing screen layouts...');
            $command = new ArrayInput([
                'command' => 'app:screen-layouts:update',
            ]);
            $application->doRun($command, $output);
        }

        return Command::SUCCESS;
    }
}
