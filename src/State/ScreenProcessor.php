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
        ScreenProvider $provider,
    ) {
        parent::__construct($entityManager, $persistProcessor, $removeProcessor, $provider);
    }

    protected function fromInput(mixed $object, Operation $operation, array $uriVariables, array $context): Screen
    {
        // FIXME Do we really have to do (something like) this to load an existing object into the entity manager?
        $screen = $this->loadPrevious(new Screen(), $context);

        if (!$screen instanceof Screen) {
            throw new InvalidArgumentException('object must be of type Screen');
        }

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

        // Adding relations for playlist/screen/region if object has region property.
        if (isset($object->regions)) {
            // Ensure regions object has valid structure
            $this->validateRegionsAndPlaylists($object->regions);

            $existingPlaylistScreenRegions = $screen->getPlaylistScreenRegions();

            foreach ($object->regions as $regionAndPlaylists) {
                $regionId = $regionAndPlaylists['regionId'];

                $region = $this->screenLayoutRegionsRepository->findOneBy(['id' => $regionId]);

                if (is_null($region)) {
                    throw new InvalidArgumentException(sprintf('Unknown region resource (id: %s)', $regionId));
                }

                $existingPlaylistScreenRegionsInRegion = $existingPlaylistScreenRegions->filter(
                    fn (PlaylistScreenRegion $psr) => $psr->getRegion()?->getId() == $regionId
                );

                $inputPlaylists = $regionAndPlaylists['playlists'];
                $inputPlaylistIds = array_map(fn (array $entry): string => $entry['id'], $inputPlaylists);

                // Remove playlist screen regions that should not exist in region.
                /** @var PlaylistScreenRegion $existingPSR */
                foreach ($existingPlaylistScreenRegionsInRegion as $existingPSR) {
                    if (!in_array($existingPSR->getPlaylist()?->getId(), $inputPlaylistIds)) {
                        $screen->removePlaylistScreenRegion($existingPSR);
                    }
                }

                // Add or update the input playlists.
                foreach ($inputPlaylists as $inputPlaylist) {
                    $playlist = $this->playlistRepository->findOneBy(['id' => $inputPlaylist['id']]);

                    if (is_null($playlist)) {
                        throw new InvalidArgumentException(sprintf('Unknown playlist resource (id: %s)', $inputPlaylist['id']));
                    }

                    $existingPlaylistScreenRegionRelation = $this->playlistScreenRegionRepository->findOneBy([
                        'playlist' => $playlist,
                        'region' => $region,
                        'screen' => $screen,
                    ]);

                    if (!is_null($existingPlaylistScreenRegionRelation)) {
                        $existingPlaylistScreenRegionRelation->setWeight($inputPlaylist['weight'] ?? 0);
                    } else {
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
        if (isset($object->groups)) {
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

    private function validateRegionsAndPlaylists(array $regions): void
    {
        foreach ($regions as $region) {
            $this->validateRegion($region);

            foreach ($region['playlists'] as $playlist) {
                $this->validatePlaylist($playlist);
            }
        }
    }

    private function validateRegion(array $region): void
    {
        if (!isset($region['regionId']) || !is_string($region['regionId'])) {
            throw new InvalidArgumentException('All regions must specify a valid Ulid');
        }

        if (!isset($region['playlists']) || !is_array($region['playlists'])) {
            throw new InvalidArgumentException('All regions must specify a list of playlists');
        }
    }

    private function validatePlaylist(array $playlist): void
    {
        if (!isset($playlist['id']) || !is_string($playlist['id'])) {
            throw new InvalidArgumentException('All playlists must specify a valid Ulid');

        }

        if (isset($playlist['weight']) && !is_integer($playlist['weight'])) {
            throw new InvalidArgumentException('Playlists weight must be an integer');
        }
    }
}
