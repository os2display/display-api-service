<?php

namespace App\Command;

use App\Entity\ScreenLayout;
use App\Entity\ScreenLayoutRegions;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Id\AssignedGenerator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
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

    protected function configure(): void
    {
        $this->addArgument('filename', InputArgument::REQUIRED, 'json file to load. Can be local file or a URL');
    }

    final protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $filename = $input->getArgument('filename');
            $content = json_decode(file_get_contents($filename), false, 512, JSON_THROW_ON_ERROR);

            if (isset($content->id) && Ulid::isValid($content->id)) {
                $repository = $this->entityManager->getRepository(ScreenLayout::class);
                $loadedScreenLayout = $repository->findOneBy(['id' => Ulid::fromString($content->id)]);

                if (!$loadedScreenLayout) {
                    $screenLayout = new ScreenLayout();
                    $metadata = $this->entityManager->getClassMetaData(get_class($screenLayout));
                    $metadata->setIdGenerator(new AssignedGenerator());
                    $this->entityManager->persist($screenLayout);

                    $ulid = Ulid::fromString($content->id);

                    $screenLayout->setId($ulid));
                    $screenLayout->setCreatedAt(\DateTime::createFromImmutable($ulid->getDateTime()));
                } else {
                    $screenLayout = $loadedScreenLayout;
                }
            } else {
                $io->error('The screen layout should have an id (ulid)');

                return Command::INVALID;
            }

            $screenLayout->setTitle($content->title);
            $screenLayout->setGridColumns($content->grid->columns);
            $screenLayout->setGridRows($content->grid->rows);

            foreach ($content->regions as $localRegion) {
                $region = new ScreenLayoutRegions();
                $region->setGridArea($localRegion->gridArea);
                $region->setTitle($localRegion->title);
                $this->entityManager->persist($region);
                $screenLayout->addRegion($region);
            }

            $this->entityManager->flush();

            $io->success('Screen layout added');

            return Command::SUCCESS;
        } catch (\JsonException $exception) {
            $io->error('Invalid json');

            return Command::INVALID;
        }
    }
}
