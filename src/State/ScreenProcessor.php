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

                $newPlaylistULIDs = array_map(
                    /**
                     * @param array<mixed> $playlistObject
                     *
                     * @return Ulid
                     */
                    fn ($playlistObject): Ulid => Ulid::fromString($playlistObject['id']), $regionAndPlaylists['playlists']);

                $playlistScreens = $this->playlistScreenRegionRepository->findBy([
                    'screen' => $screen->getId(),
                    'region' => $regionAndPlaylists['regionId'],
                ]);

                $existingPlaylistsULIDs = array_map(function ($playlistObject) {
                    $playlist = $playlistObject->getPlaylist();
                    if (!is_null($playlist)) {
                        return $playlist->getId();
                    }
                }, $playlistScreens);

                // This diff finds the playlists to be deleted
                $deletePlaylistsULIDs = array_diff($existingPlaylistsULIDs, $newPlaylistULIDs);

                // ... and deletes them.
                foreach ($deletePlaylistsULIDs as $deletePlaylist) {
                    $regionId = $region->getId();
                    $screenId = $screen->getId();
                    if (!is_null($screenId) && !is_null($regionId) && !is_null($deletePlaylist)) {
                        $this->playlistScreenRegionRepository->deleteRelations($screenId, $regionId, $deletePlaylist);
                    }
                }

                // This diff finds the playlists to be saved
                $newPlaylistULIDs = array_diff($newPlaylistULIDs, $existingPlaylistsULIDs);

                // ... and saves them.
                foreach ($newPlaylistULIDs as $newPlaylist) {
                    $playlistAndRegionToSave = new PlaylistScreenRegion();
                    $playlist = $this->playlistRepository->findOneBy(['id' => $newPlaylist]);

                    if (is_null($playlist)) {
                        throw new InvalidArgumentException('Unknown playlist resource');
                    }

                    // Filter the array containing all the new playlists, to find the weight of the playlist currently
                    // set for save
                    $playlistWeight = array_filter($regionAndPlaylists['playlists'],
                        /**
                         * @param array<mixed> $playlistAndWeight
                         *
                         * @return bool
                         */
                        fn ($playlistAndWeight) => Ulid::fromString($playlistAndWeight['id']) == $playlist->getId());

                    $playlistAndRegionToSave->setPlaylist($playlist);
                    $playlistAndRegionToSave->setRegion($region);
                    if (count($playlistWeight) > 0 && isset($playlistWeight[0]['weight'])) {
                        $playlistAndRegionToSave->setWeight($playlistWeight[0]['weight']);
                    } else {
                        $playlistAndRegionToSave->setWeight(0);
                    }
                    $screen->addPlaylistScreenRegion($playlistAndRegionToSave);
                }

                $uneditedPlaylists = array_diff(array_diff($existingPlaylistsULIDs, $deletePlaylistsULIDs), $newPlaylistULIDs);

                foreach ($existingPlaylistsULIDs as $existingPlaylist) {
                    $region = $this->screenLayoutRegionsRepository->findOneBy(['id' => $regionAndPlaylists['regionId']]);

                    if (is_null($region)) {
                        throw new InvalidArgumentException('Unknown region resource');
                    }

                    $playlist = $this->playlistRepository->findOneBy(['id' => $existingPlaylist]);

                    if (is_null($playlist)) {
                        throw new InvalidArgumentException('Unknown playlist resource');
                    }

                    $psr = $this->playlistScreenRegionRepository->findOneBy([
                        'screen' => $screen->getId(),
                        'region' => $region,
                        'playlist' => $playlist,
                    ]);

                    // Filter the array containing all the new playlists, to find the weight of the playlist currently
                    // set for save
                    $playlistWeight = array_filter($regionAndPlaylists['playlists'],
                        /**
                         * @param array<mixed> $playlistAndWeight
                         *
                         * @return bool
                         */
                        fn ($playlistAndWeight) => Ulid::fromString($playlistAndWeight['id']) == $existingPlaylist);

                    if (count($playlistWeight) > 0) {
                        $psr->setWeight(reset($playlistWeight)['weight']);
                    } else {
                        $psr->setWeight(0);
                    }
                }
            }
        }

        // Maps ids of existing groups
        if (isset($object->groups) && isset($screen)) {
            $existingGroups = array_map(function ($group) {
                if (!is_null($group)) {
                    return $group->getId();
                }
            }, iterator_to_array($screen->getScreenGroups()));

            // Ids of groups inputted
            $newGroupsId = array_map(
                /**
                 * @param string $group
                 *
                 * @return Ulid
                 */
                fn ($group): Ulid => Ulid::fromString($group), $object->groups);

            // This diff finds the groups to be saved
            $newGroups = array_diff($newGroupsId, $existingGroups);
            // ... and saves them.
            foreach ($newGroups as $group) {
                $groupToSave = $this->groupRepository->findOneBy(['id' => $group]);

                if (is_null($groupToSave)) {
                    throw new InvalidArgumentException('Unknown group resource');
                }

                $screen->addScreenGroup($groupToSave);
            }

            // This diff finds the groups to be deleted
            $deleteGroups = array_diff($existingGroups, $newGroupsId);
            // ... and deletes them.
            foreach ($deleteGroups as $group) {
                $groupToDelete = $this->groupRepository->findOneBy(['id' => $group]);

                if (is_null($groupToDelete)) {
                    throw new InvalidArgumentException('Unknown group resource');
                }

                $screen->removeScreenGroup($groupToDelete);
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
