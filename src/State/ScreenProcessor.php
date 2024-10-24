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
        private readonly EntityManagerInterface $entityManager,
        ProcessorInterface $persistProcessor,
        ProcessorInterface $removeProcessor,
        ScreenProvider $provider
    )
    {
        parent::__construct($entityManager, $persistProcessor, $removeProcessor, $provider);
    }

    protected function fromInput(mixed $object, Operation $operation, array $uriVariables, array $context): Screen
    {
        /** @var Screen $screen */
        // FIXME Do we really have to do (something like) this to load an existing object into the entity manager?
        $screen = $this->loadPrevious(new Screen(), $context);

        assert($object instanceof ScreenInput);
        empty($object->title) ?: $screen->setTitle($object->title);
        empty($object->description) ?: $screen->setDescription($object->description);
        empty($object->createdBy) ?: $screen->setCreatedBy($object->createdBy);
        empty($object->modifiedBy) ?: $screen->setModifiedBy($object->modifiedBy);
        empty($object->size) ?: $screen->setSize((int)$object->size);
        empty($object->location) ?: $screen->setLocation($object->location);
        empty($object->orientation) ?: $screen->setOrientation($object->orientation);
        empty($object->resolution) ?: $screen->setResolution($object->resolution);

        if (isset($object->enableColorSchemeChange)) {
            $screen->setEnableColorSchemeChange($object->enableColorSchemeChange);
        }

        // Adding relations for playlist/screen/region
        if (isset($object->regions) && isset($screen)) {
            $psrs = $screen->getPlaylistScreenRegions();

            foreach ($object->regions as $regionAndPlaylists) {
                $regionId = $regionAndPlaylists['regionId'];

                $region = $this->screenLayoutRegionsRepository->findOneBy(['id' => $regionId]);

                if (is_null($region)) {
                    throw new InvalidArgumentException('Unknown region resource');
                }

                $existingPlaylistScreenRegionsInRegion = $psrs->filter(
                    function (PlaylistScreenRegion $psr) use ($regionId) {
                        return $psr->getRegion()->getId() == $regionId;
                    }
                );

                $inputPlaylists = $regionAndPlaylists['playlists'];
                $inputPlaylistIds = array_map(function ($entry) {
                    return $entry['id'];
                }, $inputPlaylists);

                // Remove playlist screen regions that should not exist in region.
                /** @var PlaylistScreenRegion $existingPSR */
                foreach ($existingPlaylistScreenRegionsInRegion as $existingPSR) {
                    if (!in_array($existingPSR->getPlaylist()->getId(), $inputPlaylistIds)) {
                        $screen->removePlaylistScreenRegion($existingPSR);
                    }
                }

                // Add or update the input playlists.
                foreach ($inputPlaylists as $inputPlaylist) {
                    $playlist = $this->playlistRepository->findOneBy(['id' => $inputPlaylist['id']]);
                    $existing = $this->playlistScreenRegionRepository->findOneBy([
                        'playlist' => $playlist,
                        'region' => $region,
                        'screen' => $screen,
                    ]);

                    if ($existing) {
                        $existing->setWeight($inputPlaylist['weight']);
                    } else {
                        $playlist = $this->playlistRepository->findOneBy(['id' => $inputPlaylist['id']]);

                        if (is_null($playlist)) {
                            throw new InvalidArgumentException('Unknown playlist resource');
                        }

                        $newPlaylistScreenRegionRelation = new PlaylistScreenRegion();
                        $newPlaylistScreenRegionRelation->setPlaylist($playlist);
                        $newPlaylistScreenRegionRelation->setRegion($region);
                        $newPlaylistScreenRegionRelation->setScreen($screen);
                        $newPlaylistScreenRegionRelation->setWeight($inputPlaylist['weight'] ?? 0);
                        $screen->addPlaylistScreenRegion($newPlaylistScreenRegionRelation);
                    }
                }
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
                /** @psalm-suppress InvalidArgument */
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
