<?php

namespace App\DataTransformer;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use App\Dto\Slide as SlideDTO;
use App\Entity\Slide;

class SlideOutputDataTransformer implements DataTransformerInterface
{
    public function __construct(
        private IriConverterInterface $iriConverter
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function transform($slide, string $to, array $context = []): SlideDTO
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

        foreach ($slide->getPlaylists() as $playlist) {
            $output->onPlaylists[] = $this->iriConverter->getIriFromItem($playlist);
        }

        foreach ($slide->getMedia() as $media) {
            $output->media[] = $this->iriConverter->getIriFromItem($media);
        }

        $output->duration = $slide->getDuration();
        $output->published = [
            'from' => $slide->getPublishedFrom(),
            'to' => $slide->getPublishedTo(),
        ];
        $output->content = $slide->getContent();

        return $output;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        return SlideDTO::class === $to && $data instanceof Slide;
    }
}
