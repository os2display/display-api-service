<?php

namespace App\State;

use ApiPlatform\Api\IriConverterInterface;
use ApiPlatform\State\ProviderInterface;
use App\Dto\Screen as ScreenDTO;
use App\Entity\Tenant\Screen;
use App\Repository\ScreenRepository;

class ScreenProvider extends AbstractProvider
{
    public function __construct(
        private readonly IriConverterInterface $iriConverter,
        ProviderInterface $collectionProvider,
        ScreenRepository $entityRepository,
    ) {
        parent::__construct($collectionProvider, $entityRepository);
    }

    protected function toOutput(object $object): ScreenDTO
    {
        assert($object instanceof Screen);

        $output = new ScreenDTO();
        $output->id = $object->getId();
        $output->title = $object->getTitle();
        $output->description = $object->getDescription();
        $output->created = $object->getCreatedAt();
        $output->modified = $object->getModifiedAt();
        $output->createdBy = $object->getCreatedBy();
        $output->modifiedBy = $object->getModifiedBy();
        $output->size = (string) $object->getSize();
        $output->orientation = $object->getOrientation();
        $output->resolution = $object->getResolution();

        $output->enableColorSchemeChange = $object->getEnableColorSchemeChange();

        $layout = $object->getScreenLayout();
        $output->layout = $this->iriConverter->getIriFromResource($layout);

        $output->location = $object->getLocation();

        $iri = $this->iriConverter->getIriFromResource($object);
        $output->campaigns = $iri.'/campaigns';

        $objectIri = $this->iriConverter->getIriFromResource($object);
        foreach ($layout->getRegions() as $region) {
            $output->regions[] = $objectIri.'/regions/'.$region->getId().'/playlists';
        }
        $output->inScreenGroups = $objectIri.'/screen-groups';

        $objectUser = $object->getScreenUser();

        if (null != $objectUser) {
            $objectUserId = $objectUser->getId();
            if (null != $objectUserId) {
                $output->screenUser = $objectUserId->jsonSerialize();
            }
        }

        return $output;
    }
}
