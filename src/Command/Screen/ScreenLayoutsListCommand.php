<?php

declare(strict_types=1);

namespace App\Command\Screen;

use App\Model\ScreenLayoutData;
use App\Service\ScreenLayoutService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:screen-layouts:list',
    description: 'List screen layouts',
)]
class ScreenLayoutsListCommand extends Command
{
    public function __construct(
        private readonly ScreenLayoutService $screenLayoutService,
    ) {
        parent::__construct();
    }

    final protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $screenLayouts = $this->screenLayoutService->getAllScreenLayouts();

            if (0 === count($screenLayouts)) {
                $io->error('No screen layouts found.');

                return Command::INVALID;
            }

            $io->table(['ID', 'Title', 'Status', 'Type'], array_map(fn (ScreenLayoutData $screenLayout) => [
                $screenLayout->id,
                $screenLayout->title,
                $screenLayout->installed ? 'Installed' : 'Not Installed',
                $screenLayout->type,
            ], $screenLayouts));

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error($e->getMessage());

            return Command::INVALID;
        }
    }
}
