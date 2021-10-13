<?php

namespace App\Command;

use App\Entity\ScreenLayout;
use App\Entity\ScreenLayoutRegions;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Uid\Ulid;

#[AsCommand(
    name: 'app:screen-layouts:load',
    description: 'Load a set of predefined screen layouts',
)]
class LoadScreenLayoutsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    final protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $screenLayout = $this->entityManager->find(ScreenLayout::class, new Ulid('01FHW99G74X3SJEGPMCXDJGYXQ'));
        if ($screenLayout) {
            $io->info('Aborting - ScreenLayout already loaded');

            return Command::INVALID;
        }

        $region = new ScreenLayoutRegions();
        $region->setTitle('Main');
        $region->setGridArea(['a']);

        $screenLayout = new ScreenLayout();
        $screenLayout->setTitle('Full screen');
        $screenLayout->setDescription('Layout with one "full screen" region');
        $screenLayout->setGridColumns(1);
        $screenLayout->setGridRows(1);
        $screenLayout->addRegion($region);

        $this->entityManager->persist($region);
        $this->entityManager->persist($screenLayout);
        $this->entityManager->flush();

        $io->success('Screen layout "full screen" added');

        return Command::SUCCESS;
    }
}
