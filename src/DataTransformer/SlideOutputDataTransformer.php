<?php

namespace App\DataTransformer;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use App\Dto\Slide as SlideDTO;
use App\Entity\Slide;

class SlideOutputDataTransformer implements DataTransformerInterface
{
    private IriConverterInterface $iriConverter;

    public function __construct(IriConverterInterface $iriConverter)
    {
        $this->iriConverter = $iriConverter;
    }

    public function transform($slide, string $to, array $context = [])
    {
        /** @var Slide $slide */
        $output = new SlideDTO();
        $output->title = $slide->getTitle();
        $output->description = $slide->getDescription();
        $output->created = $slide->getCreatedAt();
        $output->modified = $slide->getUpdatedAt();
        $output->createdBy = $slide->getCreatedBy();
        $output->modifiedBy = $slide->getModifiedBy();

        $output->templateInfo = [
            '@id' => $this->iriConverter->getIriFromItem($slide->getTemplate()),
            'options' => $slide->getTemplateOptions(),
        ];

//        foreach ($slide->getScreen() as $screen) {
//            $output->onScreens[] = $this->iriConverter->getIriFromItem($screen);
//        }

        foreach ($slide->getPlaylists() as $playlist) {
            $output->onPlaylists[] = $this->iriConverter->getIriFromItem($playlist);
        }

        $output->duration = $slide->getDuration();
        $output->published = [
            'from' => $slide->getPublishedFrom(),
            'to' => $slide->getPublishedTo(),
        ];
        $output->content = $slide->getContent();

        return $output;
    }

    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        return SlideDTO::class === $to && $data instanceof Slide;
    }
}
