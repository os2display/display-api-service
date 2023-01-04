<?php

namespace App\DataTransformer;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use App\Dto\PlaylistInput;
use App\Entity\Tenant\Playlist;
use App\Entity\Tenant\Schedule;
use App\Repository\PlaylistScreenRegionRepository;
use App\Repository\TenantRepository;
use App\Utils\IriHelperUtils;
use App\Utils\ValidationUtils;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

final class PlaylistInputDataTransformer implements DataTransformerInterface
{
    public function __construct(
        private ValidationUtils $utils,
        private IriHelperUtils $iriHelperUtils,
        private TenantRepository $tenantRepository,
        private PlaylistScreenRegionRepository $playlistScreenRegionRepository
    ) {}

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public function transform($data, string $to, array $context = []): Playlist
    {
        $playlist = new Playlist();
        if (array_key_exists(AbstractNormalizer::OBJECT_TO_POPULATE, $context)) {
            $playlist = $context[AbstractNormalizer::OBJECT_TO_POPULATE];
        }

        /* @var PlaylistInput $data */
        empty($data->title) ?: $playlist->setTitle($data->title);
        empty($data->description) ?: $playlist->setDescription($data->description);
        empty($data->isCampaign) ?: $playlist->setIsCampaign($data->isCampaign);

        // Remove all tenants.
        if (isset($data->tenants)) {
            $playlistTenants = [];
            if (count($playlist->getTenants()) >= 0) {
                $playlistTenants = array_map(fn ($value): string => $value->getId()->toBase32(), $playlist->getTenants()->toArray());
            }

            // Deletes playlist-screen-region relation, if a playlist is no longer shared
            $diff = array_diff($playlistTenants, $data->tenants);
            foreach ($diff as $tenantId) {
                $this->playlistScreenRegionRepository->deleteRelationsPlaylistsTenant($playlist->getId(), $tenantId);
            }
            foreach ($playlist->getTenants() as $tenant) {
                $playlist->removeTenant($tenant);
            }
        }

        // Add tenants.
        if (!empty($data->tenants)) {
            foreach ($data->tenants as $tenantId) {
                // Get tenant
                $tenant = $this->tenantRepository->findOneBy(['id' => $tenantId]);
                $playlist->addTenant($tenant);
            }
        }

        // Remove all schedules.
        if (isset($data->schedules)) {
            foreach ($playlist->getSchedules() as $schedule) {
                $playlist->removeSchedule($schedule);
            }
        }

        // Add schedules.
        if (!empty($data->schedules)) {
            // Add schedules.
            foreach ($data->schedules as $scheduleData) {
                $schedule = new Schedule();
                $rrule = $this->utils->validateRRule($this->transformRRuleNewline($scheduleData['rrule']));
                $schedule->setRrule($rrule);
                $schedule->setDuration($scheduleData['duration']);
                $schedule->setPlaylist($playlist);
                $playlist->addSchedule($schedule);
            }
        }

        empty($data->createdBy) ?: $playlist->setCreatedBy($data->createdBy);
        empty($data->modifiedBy) ?: $playlist->setModifiedBy($data->modifiedBy);

        if (null === $data->published['from']) {
            $playlist->setPublishedFrom(null);
        } elseif (!empty($data->published['from'])) {
            $playlist->setPublishedFrom($this->utils->validateDate($data->published['from']));
        }

        if (null === $data->published['to']) {
            $playlist->setPublishedTo(null);
        } elseif (!empty($data->published['to'])) {
            $playlist->setPublishedTo($this->utils->validateDate($data->published['to']));
        }

        return $playlist;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        if ($data instanceof Playlist) {
            return false;
        }

        return Playlist::class === $to && null !== ($context['input']['class'] ?? null);
    }

    private function transformRRuleNewline(string $rrule): string
    {
        $rrule = str_replace('\\n', PHP_EOL, $rrule);

        return str_replace('\n', PHP_EOL, $rrule);
    }
}
