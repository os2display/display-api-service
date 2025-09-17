<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\ScreenLayout;
use App\Entity\ScreenLayoutRegions;
use App\Enum\ResourceTypeEnum;
use App\Model\ScreenLayoutData;
use App\Repository\ScreenLayoutRegionsRepository;
use App\Utils\ResourceLoader;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Id\AssignedGenerator;
use Symfony\Component\Uid\Ulid;

class ScreenLayoutService
{
    public const string CORE_SCREEN_LAYOUTS_PATH = 'assets/shared/screen-layouts';
    public const string CUSTOM_SCREEN_LAYOUTS_PATH = 'assets/shared/custom-screen-layouts';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ScreenLayoutRegionsRepository $layoutRegionsRepository,
        private readonly ResourceLoader $loader,
    ) {}

    public function getScreenLayouts(): array
    {
        $core = $this->loader->getResourceJsonInDirectory($this::CORE_SCREEN_LAYOUTS_PATH, ScreenLayoutData::class, ResourceTypeEnum::CORE);
        $custom = $this->loader->getResourceJsonInDirectory($this::CUSTOM_SCREEN_LAYOUTS_PATH, ScreenLayoutData::class, ResourceTypeEnum::CUSTOM);

        return array_merge($core, $custom);
    }

    public function installScreenLayout(ScreenLayoutData $screenLayoutData, bool $update = false, bool $cleanupRegions = false): void
    {
        $screenLayout = $screenLayoutData->screenLayoutEntity;

        if (null === $screenLayout) {
            $screenLayout = new ScreenLayout();

            $metadata = $this->entityManager->getClassMetaData(ScreenLayout::class);
            $metadata->setIdGenerator(new AssignedGenerator());

            $ulid = Ulid::fromString($screenLayoutData->id);
            $screenLayout->setId($ulid);

            $this->entityManager->persist($screenLayout);
        }

        if ($update) {
            $screenLayout->setTitle($screenLayoutData->title);
        }

        $screenLayout->setGridColumns($screenLayoutData->gridColumns);
        $screenLayout->setGridRows($screenLayoutData->gridRows);

        $existingRegions = $screenLayout->getRegions();

        $processedRegionIds = [];

        foreach ($screenLayoutData->regions as $localRegion) {
            $region = $this->layoutRegionsRepository->findOneBy(['id' => Ulid::fromString($localRegion->id)]);

            if (!$region) {
                $region = new ScreenLayoutRegions();

                $metadata = $this->entityManager->getClassMetaData($region::class);
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

        if ($cleanupRegions) {
            foreach ($existingRegions as $existingRegion) {
                // Remove all regions that are not present in the json.
                if (!in_array($existingRegion->getId(), $processedRegionIds)) {
                    foreach ($existingRegion->getPlaylistScreenRegions() as $playlistScreenRegion) {
                        $this->entityManager->remove($playlistScreenRegion);
                    }

                    $this->entityManager->remove($existingRegion);
                }
            }
        }

        $this->entityManager->flush();
    }

    public function updateScreenLayout(ScreenLayoutData $screenLayoutToUpdate): void
    {
        // This only handles screen layouts that have an entity in the database.
        if (null !== $screenLayoutToUpdate->screenLayoutEntity) {
            $this->installScreenLayout($screenLayoutToUpdate, true);
        }
    }
}
