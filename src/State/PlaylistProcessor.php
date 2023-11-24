<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\PlaylistInput;
use App\Entity\Tenant;
use App\Entity\Tenant\Playlist;
use App\Entity\Tenant\Schedule;
use App\Exceptions\EntityException;
use App\Repository\PlaylistScreenRegionRepository;
use App\Repository\TenantRepository;
use App\Utils\ValidationUtils;
use Doctrine\ORM\EntityManagerInterface;

class PlaylistProcessor extends AbstractProcessor
{
    public function __construct(
        private readonly ValidationUtils $utils,
        private readonly TenantRepository $tenantRepository,
        private readonly PlaylistScreenRegionRepository $playlistScreenRegionRepository,
        EntityManagerInterface $entityManager,
        ProcessorInterface $persistProcessor,
        ProcessorInterface $removeProcessor,
        PlaylistProvider $provider
    ) {
        parent::__construct($entityManager, $persistProcessor, $removeProcessor, $provider);
    }

    /**
     * @return T
     */
    protected function fromInput(mixed $object, Operation $operation, array $uriVariables, array $context): Playlist
    {
        // FIXME Do we really have to do (something like) this to load an existing object into the entity manager?
        $playlist = $this->loadPrevious(new Playlist(), $context);

        /* @var PlaylistInput $object */
        empty($object->title) ?: $playlist->setTitle($object->title);
        empty($object->description) ?: $playlist->setDescription($object->description);
        empty($object->isCampaign) ?: $playlist->setIsCampaign($object->isCampaign);

        // Remove all tenants.
        if (isset($object->tenants)) {
            $playlistTenants = [];
            if (count($playlist->getTenants())) {
                $playlistTenants =
                    array_map(
                        function (Tenant $tenant) {
                            $tenantId = $tenant->getId();

                            if (null === $tenantId) {
                                throw new EntityException('Tenant id null');
                            }

                            return $tenantId->jsonSerialize();
                        }, $playlist->getTenants()->toArray());
            }

            // Deletes playlist-screen-region relation, if a playlist is no longer shared
            $diff = array_diff($playlistTenants, $object->tenants);

            foreach ($diff as $tenantId) {
                $playlistId = $playlist->getId();

                if (null === $playlistId) {
                    throw new EntityException('Playlist id null');
                }
                $this->playlistScreenRegionRepository->deleteRelationsPlaylistsTenant($playlistId, $tenantId);
            }

            foreach ($playlist->getTenants() as $tenant) {
                $playlist->removeTenant($tenant);
            }
        }

        // Add tenants.
        if (!empty($object->tenants)) {
            foreach ($object->tenants as $tenantId) {
                // Get tenant
                $tenant = $this->tenantRepository->findOneBy(['id' => $tenantId]);
                if (null !== $tenant) {
                    $playlist->addTenant($tenant);
                }
            }
        }

        // Remove all schedules.
        if (isset($object->schedules)) {
            foreach ($playlist->getSchedules() as $schedule) {
                $playlist->removeSchedule($schedule);
            }
        }

        // Add schedules.
        if (!empty($object->schedules)) {
            // Add schedules.
            foreach ($object->schedules as $scheduleData) {
                $schedule = new Schedule();
                $rrule = $this->utils->validateRRule($this->transformRRuleNewline($scheduleData['rrule']));
                $schedule->setRrule($rrule);
                $schedule->setDuration($scheduleData['duration']);
                $schedule->setPlaylist($playlist);
                $playlist->addSchedule($schedule);
            }
        }

        empty($object->createdBy) ?: $playlist->setCreatedBy($object->createdBy);
        empty($object->modifiedBy) ?: $playlist->setModifiedBy($object->modifiedBy);

        if (null === $object->published['from']) {
            $playlist->setPublishedFrom(null);
        } elseif (!empty($object->published['from'])) {
            $playlist->setPublishedFrom($this->utils->validateDate($object->published['from']));
        }

        if (null === $object->published['to']) {
            $playlist->setPublishedTo(null);
        } elseif (!empty($object->published['to'])) {
            $playlist->setPublishedTo($this->utils->validateDate($object->published['to']));
        }

        return $playlist;
    }

    private function transformRRuleNewline(string $rrule): string
    {
        $rrule = str_replace('\\n', PHP_EOL, $rrule);

        return str_replace('\n', PHP_EOL, $rrule);
    }
}
