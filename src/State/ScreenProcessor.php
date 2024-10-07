<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\ScreenInput;
use App\Entity\Tenant\PlaylistScreenRegion;
use App\Entity\Tenant\Screen;
use App\Repository\PlaylistRepository;
use App\Repository\PlaylistScreenRegionRepository;
use App\Repository\ScreenGroupRepository;
use App\Repository\ScreenLayoutRegionsRepository;
use App\Repository\ScreenLayoutRepository;
use App\Utils\IriHelperUtils;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Ulid;

class ScreenProcessor extends AbstractProcessor
{
    public function __construct(
        private readonly IriHelperUtils $iriHelperUtils,
        private readonly ScreenLayoutRepository $layoutRepository,
        private readonly ScreenLayoutRegionsRepository $screenLayoutRegionsRepository,
        private readonly PlaylistRepository $playlistRepository,
        private readonly PlaylistScreenRegionRepository $playlistScreenRegionRepository,
        private readonly ScreenGroupRepository $groupRepository,
        EntityManagerInterface $entityManager,
        ProcessorInterface $persistProcessor,
        ProcessorInterface $removeProcessor,
        ScreenProvider $provider
    ) {
        parent::__construct($entityManager, $persistProcessor, $removeProcessor, $provider);
    }

    protected function fromInput(mixed $object, Operation $operation, array $uriVariables, array $context): Screen
    {
        // FIXME Do we really have to do (something like) this to load an existing object into the entity manager?
        $screen = $this->loadPrevious(new Screen(), $context);

        assert($object instanceof ScreenInput);
        empty($object->title) ?: $screen->setTitle($object->title);
        empty($object->description) ?: $screen->setDescription($object->description);
        empty($object->createdBy) ?: $screen->setCreatedBy($object->createdBy);
        empty($object->modifiedBy) ?: $screen->setModifiedBy($object->modifiedBy);
        empty($object->size) ?: $screen->setSize((int) $object->size);
        empty($object->location) ?: $screen->setLocation($object->location);
        empty($object->orientation) ?: $screen->setOrientation($object->orientation);
        empty($object->resolution) ?: $screen->setResolution($object->resolution);

        if (isset($object->enableColorSchemeChange)) {
            $screen->setEnableColorSchemeChange($object->enableColorSchemeChange);
        }

        // Adding relations for playlist/screen/region
        if (isset($object->regions) && isset($screen)) {
            foreach ($object->regions as $regionAndPlaylists) {
                $region = $this->screenLayoutRegionsRepository->findOneBy(['id' => $regionAndPlaylists['regionId']]);

                if (is_null($region)) {
                    throw new InvalidArgumentException('Unknown region resource');
                }

                $newPlaylists = array_map(function ($playlistObject) {
                    return Ulid::fromString($playlistObject['id']);
                }, $regionAndPlaylists['playlists']);

                $playlistScreens = $this->playlistScreenRegionRepository->findBy([
                    'screen' => $screen->getId(),
                    'region' => $regionAndPlaylists['regionId'],
                ]);

                $existingPlaylists = array_map(function ($playlistObject) {
                    return $playlistObject->getPlaylist()->getId();
                }, $playlistScreens);

                // This diff finds the playlists to be deleted
                $deletePlaylists = array_diff($existingPlaylists, $newPlaylists);

                // ... and deletes them.
                foreach ($deletePlaylists as $deletePlaylist) {
                    $playlistScreens = $this->playlistScreenRegionRepository->deleteRelations($screen->getId(), $region->getId(), $deletePlaylist);
                }

                // This diff finds the playlists to be saved
                $newPlaylists = array_diff($newPlaylists, $existingPlaylists);

                // ... and saves them.
                foreach ($newPlaylists as $newPlaylist) {
                    $playlistAndRegionToSave = new PlaylistScreenRegion();
                    $playlist = $this->playlistRepository->findOneBy(['id' => $newPlaylist]);

                    if (is_null($playlist)) {
                        throw new InvalidArgumentException('Unknown playlist resource');
                    }

                    $playlistWeight = array_filter($regionAndPlaylists['playlists'], fn ($playlistAndWeight) => Ulid::fromString($playlistAndWeight['id']) == $playlist->getId());
                    $playlistAndRegionToSave->setPlaylist($playlist);
                    $playlistAndRegionToSave->setRegion($region);
                    $playlistAndRegionToSave->setWeight($playlistWeight[0]['weight']);
                    $screen->addPlaylistScreenRegion($playlistAndRegionToSave);
                }
            }
        }

        if (isset($object->groups) && isset($screen)) {
            $screen->removeAllScreenGroup();

            foreach ($object->groups as $group) {
                $groupToSave = $this->groupRepository->findOneBy(['id' => $group]);
                if (is_null($groupToSave)) {
                    throw new InvalidArgumentException('Unknown group resource');
                }
                $screen->addScreenGroup($groupToSave);
            }
        }

        if (!empty($object->layout)) {
            // Validate that layout IRI exists.
            $ulid = $this->iriHelperUtils->getUlidFromIRI($object->layout);

            // Try loading layout entity.
            $layout = $this->layoutRepository->findOneBy(['id' => $ulid]);
            if (is_null($layout)) {
                throw new InvalidArgumentException('Unknown layout resource');
            }

            $screen->setScreenLayout($layout);
        }

        return $screen;
    }
}
