<?php

declare(strict_types=1);

namespace App\Command\ScreenLayout;

use App\Exceptions\NotAcceptableException;
use App\Exceptions\NotFoundException;
use App\Service\ScreenLayoutService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:screen-layouts:remove',
    description: 'Remove a screen layout',
)]
class ScreenLayoutsRemoveCommand extends Command
{
    public function __construct(
        private readonly ScreenLayoutService $screenLayoutService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('ulid', InputArgument::REQUIRED, 'The ulid of the screen layout to remove');
    }

    final protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $ulid = $input->getArgument('ulid');

        if (!$ulid) {
            $io->error('No ulid supplied');

            return Command::INVALID;
        }

        try {
            $this->screenLayoutService->remove($ulid);
        } catch (NotFoundException|NotAcceptableException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $io->success('Screen layout removed.');

        return Command::SUCCESS;
    }
}
