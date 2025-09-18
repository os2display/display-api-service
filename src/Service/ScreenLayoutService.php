<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\ScreenLayout;
use App\Entity\ScreenLayoutRegions;
use App\Enum\ResourceTypeEnum;
use App\Exceptions\NotAcceptableException;
use App\Exceptions\NotFoundException;
use App\Model\InstallStatus;
use App\Model\ScreenLayoutData;
use App\Repository\ScreenLayoutRegionsRepository;
use App\Repository\ScreenLayoutRepository;
use App\Repository\ScreenRepository;
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
        private readonly ScreenRepository $screenRepository,
        private readonly ScreenLayoutRepository $screenLayoutRepository,
        private readonly ScreenLayoutRegionsRepository $layoutRegionsRepository,
        private readonly ResourceLoader $loader,
    ) {}

    public function getAll(): array
    {
        $core = $this->loader->getResourceInDirectory($this::CORE_SCREEN_LAYOUTS_PATH, ScreenLayoutData::class, ResourceTypeEnum::CORE);
        $custom = $this->loader->getResourceInDirectory($this::CUSTOM_SCREEN_LAYOUTS_PATH, ScreenLayoutData::class, ResourceTypeEnum::CUSTOM);

        return array_merge($core, $custom);
    }

    public function installAll(bool $update = false, bool $cleanupRegions = false): void
    {
        $screenLayouts = $this->getAll();

        foreach ($screenLayouts as $screenLayoutToInstall) {
            $this->install($screenLayoutToInstall, $update, $cleanupRegions);
        }
    }

    public function installById(string $ulidString, bool $update = false, bool $cleanupRegions = false): void
    {
        $screenLayoutToInstall = array_find($this->getAll(), fn (ScreenLayoutData $screenLayoutData): bool => $screenLayoutData->id === $ulidString);

        if (null === $screenLayoutToInstall) {
            throw new NotFoundException();
        }

        $this->install($screenLayoutToInstall, $update, $cleanupRegions);
    }

    public function install(ScreenLayoutData $screenLayoutData, bool $update = false, bool $cleanupRegions = false): void
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

    public function updateAll(): void
    {
        $screenLayouts = $this->getAll();

        foreach ($screenLayouts as $screenLayoutToUpdate) {
            $this->update($screenLayoutToUpdate);
        }
    }

    public function update(ScreenLayoutData $screenLayoutToUpdate): void
    {
        // This only handles screen layouts that have an entity in the database.
        if (null !== $screenLayoutToUpdate->screenLayoutEntity) {
            $this->install($screenLayoutToUpdate, true);
        }
    }

    public function remove(string $ulidString): void
    {
        $screenLayout = $this->screenLayoutRepository->findOneBy(['id' => Ulid::fromString($ulidString)]);

        if (!$screenLayout) {
            throw new NotFoundException('Screen layout not installed. Aborting.');
        }

        $screens = $this->screenRepository->findBy(['screenLayout' => $screenLayout]);
        $numberOfScreens = count($screens);

        if ($numberOfScreens > 0) {
            $message = "Aborting. Screen layout is bound to $numberOfScreens following screens:\n\n";

            foreach ($screens as $screen) {
                $id = $screen->getId();
                $message .= "$id\n";
            }

            throw new NotAcceptableException($message);
        }

        foreach ($screenLayout->getRegions() as $region) {
            $this->entityManager->remove($region);
        }

        $this->entityManager->remove($screenLayout);

        $this->entityManager->flush();
    }

    public function getInstallStatus(): InstallStatus
    {
        $screenLayouts = $this->getAll();
        $numberOfScreenLayouts = count($screenLayouts);
        $numberOfInstalledScreenLayouts = count(array_filter($screenLayouts, fn ($entry): bool => $entry->installed));

        return new InstallStatus($numberOfInstalledScreenLayouts, $numberOfScreenLayouts);
    }
}
