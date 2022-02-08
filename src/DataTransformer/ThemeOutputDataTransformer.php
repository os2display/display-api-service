<?php

namespace App\DataTransformer;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use App\Dto\Theme as ThemeDTO;
use App\Entity\Tenant\Slide;
use App\Entity\Tenant\Theme;

class ThemeOutputDataTransformer implements DataTransformerInterface
{
    public function __construct(
        private IriConverterInterface $iriConverter
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function transform($theme, string $to, array $context = []): ThemeDTO
    {
        /** @var Theme $theme */
        $output = new ThemeDTO();
        $output->title = $theme->getTitle();
        $output->description = $theme->getDescription();
        $output->modified = $theme->getUpdatedAt();
        $output->created = $theme->getCreatedAt();
        $output->modifiedBy = $theme->getModifiedBy();
        $output->createdBy = $theme->getCreatedBy();

        $output->onSlides = $theme->getSlides() ?: $theme->getSlides()->map(function (Slide $slide) {
            return $this->iriConverter->getIriFromItem($slide);
        });

        $output->css = $theme->getCssStyles();

        return $output;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        return ThemeDTO::class === $to && $data instanceof Theme;
    }
}
