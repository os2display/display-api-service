<?php

declare(strict_types=1);

namespace App\Command\ScreenLayout;

use App\Service\ScreenLayoutService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:screen-layouts:update',
    description: 'Update installed screen layouts',
)]
class ScreenLayoutsUpdateCommand extends Command
{
    public function __construct(
        private readonly ScreenLayoutService $screenLayoutService,
    ) {
        parent::__construct();
    }

    final protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->screenLayoutService->updateAll();

        $io->success('Updated all installed screen layouts');

        return Command::SUCCESS;
    }
}
