<?php

namespace App\Command;

use App\Entity\ScreenLayout;
use App\Entity\ScreenLayoutRegions;
use App\Repository\ScreenLayoutRegionsRepository;
use App\Repository\ScreenLayoutRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Id\AssignedGenerator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
        private EntityManagerInterface $entityManager,
        private ScreenLayoutRepository $screenLayoutRepository,
        private ScreenLayoutRegionsRepository $layoutRegionsRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('filename', InputArgument::REQUIRED, 'Json file to load. Can be a local file or a URL');
        $this->addOption('update', null, InputOption::VALUE_NONE, 'Update existing entities.');
        $this->addOption('cleanup-regions', null, InputOption::VALUE_NONE, 'Remove unused regions and their links to playlists.');
    }

    final protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $updating = false;

            $filename = $input->getArgument('filename');
            $content = json_decode(file_get_contents($filename), false, 512, JSON_THROW_ON_ERROR);

            $update = $input->getOption('update');
            $cleanupRegions = $input->getOption('cleanup-regions');

            $io->writeln($update ? 'update': 'no update');

            if (isset($content->id) && Ulid::isValid($content->id)) {
                $screenLayout = $this->screenLayoutRepository->findOneBy(['id' => Ulid::fromString($content->id)]);

                if (!$screenLayout) {
                    $screenLayout = new ScreenLayout();
                    $metadata = $this->entityManager->getClassMetaData(get_class($screenLayout));
                    $metadata->setIdGenerator(new AssignedGenerator());

                    $ulid = Ulid::fromString($content->id);

                    $screenLayout->setId($ulid);

                    $this->entityManager->persist($screenLayout);
                } else {
                    if (!$update) {
                        $io->error('Screen layout already exists. Use --update to update existing entities.');

                        return Command::INVALID;
                    }

                    $updating = true;
                }
            } else {
                $io->error('The screen layout should have an id (ulid)');

                return Command::INVALID;
            }

            $screenLayout->setTitle($content->title);
            $screenLayout->setGridColumns($content->grid->columns);
            $screenLayout->setGridRows($content->grid->rows);

            $existingRegions = $screenLayout->getRegions();

            $processedRegionIds = [];

            foreach ($content->regions as $localRegion) {
                $region = $this->layoutRegionsRepository->findOneBy(['id' => Ulid::fromString($localRegion->id)]);

                if (!$region) {
                    $region = new ScreenLayoutRegions();

                    $metadata = $this->entityManager->getClassMetaData(get_class($region));
                    $metadata->setIdGenerator(new AssignedGenerator());

                    $ulid = Ulid::fromString($localRegion->id);

                    $region->setId($ulid);

                    $this->entityManager->persist($region);

                    $screenLayout->addRegion($region);
                }

                $region->setGridArea($localRegion->gridArea);
                $region->setTitle($localRegion->title);

                if (isset($localRegion->type)) {
                    $region->setType($localRegion->type);
                }

                $processedRegionIds[] = $region->getId();
            }

            foreach ($existingRegions as $existingRegion) {
                // Remove all regions that are not present in the json.
                if (!in_array($existingRegion->getId(), $processedRegionIds)) {
                    if (!$cleanupRegions) {
                        $io->error("Removing not permitted. Playlists linked to the removed regions will be unlinked. Use --cleanup-regions option to remove regions not in json.");

                        return Command::INVALID;
                    } else {
                        foreach ($existingRegion->getPlaylistScreenRegions() as $playlistScreenRegion) {
                            $this->entityManager->remove($playlistScreenRegion);
                        }

                        $this->entityManager->remove($existingRegion);
                    }
                }
            }

            $this->entityManager->flush();

            $updating ?
                $io->success('Screen layout updated.') :
                $io->success('Screen layout added.');

            return Command::SUCCESS;
        } catch (\JsonException $exception) {
            $io->error('Invalid json');

            return Command::INVALID;
        }
    }
}
