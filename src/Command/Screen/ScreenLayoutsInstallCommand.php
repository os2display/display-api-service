<?php

declare(strict_types=1);

namespace App\Command\Screen;

use App\Model\ScreenLayoutData;
use App\Service\ScreenLayoutService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:screen-layouts:install',
    description: 'Install screen-layout(s)',
)]
class ScreenLayoutsInstallCommand extends Command
{
    public function __construct(
        private readonly ScreenLayoutService $screenLayoutService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('all', 'a', InputOption::VALUE_NONE, 'Install all available screen layouts');
        $this->addOption('update', 'u', InputOption::VALUE_NONE, 'Update already installed screen layouts');
        $this->addOption('cleanupRegions', 'c', InputOption::VALUE_NONE, 'Remove regions that are no longer used');
        $this->addArgument('screenLayoutUlid', InputArgument::OPTIONAL, 'Install the screen layout with the given ULID');
    }

    final protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $all = $input->getOption('all');
        $update = $input->getOption('update');
        $cleanupRegions = $input->getOption('cleanupRegions');

        $screenLayouts = $this->screenLayoutService->getAllScreenLayouts();

        if ($all) {
            foreach ($screenLayouts as $screenLayoutToInstall) {
                $this->screenLayoutService->installScreenLayout($screenLayoutToInstall, $update, $cleanupRegions);
            }

            $io->success('Installed all available screen layouts');

            return Command::SUCCESS;
        }

        $screenLayoutUlid = $input->getArgument('screenLayoutUlid');

        if (null === $screenLayoutUlid) {
            $io->warning('Screen layout ULID not supplied.');

            return Command::INVALID;
        }

        $screenLayoutsFound = array_find($screenLayouts, fn (ScreenLayoutData $screenLayoutData): bool => $screenLayoutData->id === $screenLayoutUlid);

        if (1 !== count($screenLayoutsFound)) {
            $io->error('Screen layout not found.');

            return Command::FAILURE;
        }

        $screenLayoutToInstall = $screenLayoutsFound[0];

        $this->screenLayoutService->installScreenLayout($screenLayoutToInstall, $update, $cleanupRegions);
        $io->success('Screen layout '.$screenLayoutToInstall->title.' installed');

        return Command::SUCCESS;
    }
}
