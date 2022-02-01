<?php

namespace App\DataTransformer;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use App\Dto\Screen as ScreenDTO;
use App\Entity\Screen;

class ScreenOutputDataTransformer implements DataTransformerInterface
{
    public function __construct(
        private IriConverterInterface $iriConverter
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function transform($screen, string $to, array $context = []): ScreenDTO
    {
        /** @var Screen $screen */
        $output = new ScreenDTO();
        $output->title = $screen->getTitle();
        $output->description = $screen->getDescription();
        $output->created = $screen->getCreatedAt();
        $output->modified = $screen->getUpdatedAt();
        $output->createdBy = $screen->getCreatedBy();
        $output->modifiedBy = $screen->getModifiedBy();
        $output->size = (string) $screen->getSize();
        $output->dimensions = [
            'width' => $screen->getResolutionWidth(),
            'height' => $screen->getResolutionHeight(),
        ];

        $layout = $screen->getScreenLayout();
        $output->layout = $this->iriConverter->getIriFromItem($layout);

        $output->location = $screen->getLocation();

        $iri = $this->iriConverter->getIriFromItem($screen);
        $output->campaigns = $iri.'/campaigns';

        $screenIri = $this->iriConverter->getIriFromItem($screen);
        foreach ($layout->getRegions() as $region) {
            $output->regions[] = $screenIri.'/regions/'.$region->getId().'/playlists';
        }
        $output->inScreenGroups = $screenIri.'/groups';

        $screenUser = $screen->getScreenUser();
        $output->screenUser = $screenUser?->getId();

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
