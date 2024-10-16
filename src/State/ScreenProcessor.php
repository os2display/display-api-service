<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\ScreenInput;
use App\Entity\Tenant\Playlist;
use App\Entity\Tenant\PlaylistScreenRegion;
use App\Entity\Tenant\Screen;
use App\Repository\PlaylistRepository;
use App\Repository\PlaylistScreenRegionRepository;
use App\Repository\ScreenGroupRepository;
use App\Repository\ScreenLayoutRegionsRepository;
use App\Repository\ScreenLayoutRepository;
use App\Utils\IriHelperUtils;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;

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
                // Relevant region
                $region = $this->screenLayoutRegionsRepository->findOneBy(['id' => $regionAndPlaylists['regionId']]);

                if (is_null($region)) {
                    throw new InvalidArgumentException('Unknown region resource');
                }

                // Collection to be saved.
                $playlistScreenRegionCollection = new ArrayCollection();

                // Looping through playlists connected to region
                foreach ($regionAndPlaylists['playlists'] as $inputPlaylist) {
                    // Checking if playlists exists
                    $playlist = $this->playlistRepository->findOneBy(['id' => $inputPlaylist['id']]);

                    if (is_null($playlist)) {
                        throw new InvalidArgumentException('Unknown playlist resource');
                    }

                    // See if relation already exists
                    $existingPlaylistScreenRegion = $this->playlistScreenRegionRepository->findOneBy([
                        'screen' => $screen,
                        'region' => $region,
                        'playlist' => $playlist,
                    ]);

                    if (is_null($existingPlaylistScreenRegion)) {
                        // If relation does not exist, create new PlaylistScreenRegion
                        $newPlaylistScreenRegionRelation = new PlaylistScreenRegion();
                        $newPlaylistScreenRegionRelation->setPlaylist($playlist);
                        $newPlaylistScreenRegionRelation->setRegion($region);
                        $newPlaylistScreenRegionRelation->setScreen($screen);
                        $newPlaylistScreenRegionRelation->setWeight($inputPlaylist['weight'] ?? 0);
                        $playlistScreenRegionCollection->add($newPlaylistScreenRegionRelation);
                    } else {
                        // Update weight, add existing relation
                        $existingPlaylistScreenRegion->setWeight($inputPlaylist['weight'] ?? 0);
                        $playlistScreenRegionCollection->add($existingPlaylistScreenRegion);
                    }
                }
                $region->setPlaylistScreenRegions($playlistScreenRegionCollection);
            }
        }

        // Maps ids of existing groups
        if (isset($object->groups) && isset($screen)) {
            $groupCollection = new ArrayCollection();
            foreach ($object->groups as $group) {
                $groupToSave = $this->groupRepository->findOneBy(['id' => $group]);
                if (is_null($groupToSave)) {
                    throw new InvalidArgumentException('Unknown screen group resource');
                }
                $groupCollection->add($groupToSave);
            }
            $screen->setScreenGroups($groupCollection);
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
