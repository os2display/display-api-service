<?php

namespace App\DataTransformer;

use ApiPlatform\Core\Api\IriConverterInterface;
use App\Dto\Screen as ScreenDTO;
use App\Entity\Screen;
use Symfony\Component\HttpFoundation\RequestStack;
use Vich\UploaderBundle\Storage\StorageInterface;

class ScreenOutputDataTransformer extends AbstractOutputDataTransformer
{
    public function __construct(
        protected RequestStack $requestStack,
        protected StorageInterface $storage,
        private IriConverterInterface $iriConverter
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function transform($screen, string $to, array $context = []): ScreenDTO
    {
        /** @var Screen $screen */
        $output = parent::transform($screen, $to, $context);

        $output->size = (string) $screen->getSize();
        $output->dimensions = [
            'width' => $screen->getResolutionWidth(),
            'height' => $screen->getResolutionHeight(),
        ];

        $layout = $screen->getScreenLayout();
        $output->layout = $this->iriConverter->getIriFromItem($layout);

        $output->location = $screen->getLocation();

        $screenIri = $this->iriConverter->getIriFromItem($screen);
        foreach ($layout->getRegions() as $region) {
            $output->regions[] = $screenIri.'/regions/'.$region->getId().'/playlists';
        }
        $output->inScreenGroups = $screenIri.'/groups';

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
