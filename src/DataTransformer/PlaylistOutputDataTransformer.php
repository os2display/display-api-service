<?php

namespace App\DataTransformer;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use App\Dto\Playlist as PlaylistDTO;
use App\Entity\Tenant\Playlist;
use App\Entity\Tenant\ScreenCampaign;
use App\Entity\Tenant\ScreenGroupCampaign;

class PlaylistOutputDataTransformer implements DataTransformerInterface
{
    public function __construct(
        private IriConverterInterface $iriConverter
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function transform($playlist, string $to, array $context = []): PlaylistDTO
    {
        /** @var Playlist $playlist */
        $output = new PlaylistDTO();
        $output->title = $playlist->getTitle();
        $output->description = $playlist->getDescription();
        $output->isCampaign = $playlist->getIsCampaign();
        $schedulesOutput = [];
        foreach ($playlist->getSchedules() as $schedule) {
            $schedulesOutput[] = [
                'id' => $schedule->getId(),
                'rrule' => $this->transformRRuleNewline($schedule->getRrule()->rfcString(true)),
                'duration' => $schedule->getDuration(),
            ];
        }
        $output->schedules = $schedulesOutput;

        $output->campaignScreens = $playlist->getScreenCampaigns()->map(function (ScreenCampaign $screenCampaign) {
            return $this->iriConverter->getIriFromItem($screenCampaign->getScreen());
        });

        $output->campaignScreenGroups = $playlist->getScreenGroupCampaigns()->map(function (ScreenGroupCampaign $screenGroupCampaign) {
            return $this->iriConverter->getIriFromItem($screenGroupCampaign->getScreenGroup());
        });

        $output->tenants = $playlist->getTenants();

        $output->created = $playlist->getCreatedAt();
        $output->modified = $playlist->getModifiedAt();
        $output->createdBy = $playlist->getCreatedBy();
        $output->modifiedBy = $playlist->getModifiedBy();

        $iri = $this->iriConverter->getIriFromItem($playlist);
        $output->slides = $iri.'/slides';

        $output->published = [
            'from' => $playlist->getPublishedFrom(),
            'to' => $playlist->getPublishedTo(),
        ];

        return $output;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        return PlaylistDTO::class === $to && $data instanceof Playlist;
    }

    private function transformRRuleNewline(string $rrule): string
    {
        return str_replace(PHP_EOL, '\\n', $rrule);
    }
}
