<?php

declare(strict_types=1);

namespace App\Command\ScreenLayout;

use App\Repository\ScreenLayoutRepository;
use App\Repository\ScreenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Uid\Ulid;

#[AsCommand(
    name: 'app:screen-layouts:remove',
    description: 'Remove a screen layout',
)]
class ScreenLayoutRemoveCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ScreenLayoutRepository $screenLayoutRepository,
        private readonly ScreenRepository $screenRepository,
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

        $screenLayout = $this->screenLayoutRepository->findOneBy(['id' => Ulid::fromString($ulid)]);

        if (!$screenLayout) {
            $io->error('Screen layout not installed. Aborting.');

            return self::INVALID;
        }

        $screens = $this->screenRepository->findBy(['screenLayout' => $screenLayout]);
        $numberOfScreens = count($screens);

        if ($numberOfScreens > 0) {
            $message = "Aborting. Screen layout is bound to $numberOfScreens following screens:\n\n";

            foreach ($screens as $screen) {
                $id = $screen->getId();
                $message .= "$id\n";
            }

            $io->error($message);

            return self::INVALID;
        }

        foreach ($screenLayout->getRegions() as $region) {
            $this->entityManager->remove($region);
        }

        $this->entityManager->remove($screenLayout);

        $this->entityManager->flush();

        $io->success('Screen layout removed.');

        return Command::SUCCESS;
    }
}
