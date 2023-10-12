<?php

namespace App\State;

use ApiPlatform\Api\IriConverterInterface;
use ApiPlatform\State\ProviderInterface;
use App\Dto\Playlist as PlaylistDTO;
use App\Entity\Tenant\Playlist;
use App\Entity\Tenant\ScreenCampaign;
use App\Entity\Tenant\ScreenGroupCampaign;
use App\Repository\PlaylistRepository;

class PlaylistProvider extends AbstractProvider
{
    public function __construct(
        private IriConverterInterface $iriConverter,
        ProviderInterface $collectionProvider,
        PlaylistRepository $entityRepository,
    ) {
        parent::__construct($collectionProvider, $entityRepository);
    }

    protected function toOutput(object $object): object
    {
        /** @var Playlist $object */
        $output = new PlaylistDTO();
        $output->title = $object->getTitle();
        $output->description = $object->getDescription();
        $output->isCampaign = $object->getIsCampaign();
        $schedulesOutput = [];
        foreach ($object->getSchedules() as $schedule) {
            $schedulesOutput[] = [
                'id' => $schedule->getId(),
                'rrule' => $this->transformRRuleNewline($schedule->getRrule()->rfcString(true)),
                'duration' => $schedule->getDuration(),
            ];
        }
        $output->schedules = $schedulesOutput;

        $output->campaignScreens = $object->getScreenCampaigns()->map(function (ScreenCampaign $screenCampaign) {
            return $this->iriConverter->getIriFromResource($screenCampaign->getScreen());
        });

        $output->campaignScreenGroups = $object->getScreenGroupCampaigns()->map(function (ScreenGroupCampaign $screenGroupCampaign) {
            return $this->iriConverter->getIriFromResource($screenGroupCampaign->getScreenGroup());
        });

        $output->tenants = $object->getTenants();

        $output->created = $object->getCreatedAt();
        $output->modified = $object->getModifiedAt();
        $output->createdBy = $object->getCreatedBy();
        $output->modifiedBy = $object->getModifiedBy();

        $iri = $this->iriConverter->getIriFromResource($object);
        $output->slides = $iri.'/slides';

        $output->published = [
            'from' => $object->getPublishedFrom(),
            'to' => $object->getPublishedTo(),
        ];

        return $output;
    }

    private function transformRRuleNewline(string $rrule): string
    {
        return str_replace(PHP_EOL, '\\n', $rrule);
    }
}
