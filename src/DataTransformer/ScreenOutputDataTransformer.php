<?php

namespace App\DataTransformer;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use App\Dto\Screen as ScreenDTO;
use App\Entity\Tenant\Screen;

class ScreenOutputDataTransformer implements DataTransformerInterface
{
    public function __construct(
        private IriConverterInterface $iriConverter
    ) {}

    /**
     * {@inheritdoc}
     */
    public function transform($object, string $to, array $context = []): ScreenDTO
    {
        /** @var Screen $object */
        $output = new ScreenDTO();
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
        $output->layout = $this->iriConverter->getIriFromItem($layout);

        $output->location = $object->getLocation();

        $iri = $this->iriConverter->getIriFromItem($object);
        $output->campaigns = $iri.'/campaigns';

        $objectIri = $this->iriConverter->getIriFromItem($object);
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

    /**
     * {@inheritdoc}
     */
    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        return ScreenDTO::class === $to && $data instanceof Screen;
    }
}
