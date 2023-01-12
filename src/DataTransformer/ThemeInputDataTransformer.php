<?php

namespace App\DataTransformer;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use ApiPlatform\Core\Serializer\AbstractItemNormalizer;
use App\Dto\ThemeInput;
use App\Entity\Tenant\Theme;
use App\Repository\MediaRepository;
use App\Utils\IriHelperUtils;

final class ThemeInputDataTransformer implements DataTransformerInterface
{
    public function __construct(
        private IriHelperUtils $iriHelperUtils,
        private MediaRepository $mediaRepository,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function transform($data, string $to, array $context = []): Theme
    {
        $theme = new Theme();
        if (array_key_exists(AbstractItemNormalizer::OBJECT_TO_POPULATE, $context)) {
            $theme = $context[AbstractItemNormalizer::OBJECT_TO_POPULATE];
        }

        /* @var ThemeInput $data */
        empty($data->title) ?: $theme->setTitle($data->title);
        empty($data->description) ?: $theme->setDescription($data->description);
        empty($data->createdBy) ?: $theme->setCreatedBy($data->createdBy);
        empty($data->modifiedBy) ?: $theme->setModifiedBy($data->modifiedBy);
        empty($data->cssStyles) ?: $theme->setCssStyles($data->cssStyles);

        $theme->removeLogo();
        if (!empty($data->logo)) {
            // Validate that media IRI exists.
            $ulid = $this->iriHelperUtils->getUlidFromIRI($data->logo);

            // Try loading logo entity.
            $logo = $this->mediaRepository->findOneBy(['id' => $ulid]);

            if (is_null($logo)) {
                throw new InvalidArgumentException('Unknown media resource');
            }

            $theme->addLogo($logo);
        }

        return $theme;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        if ($data instanceof Theme) {
            return false;
        }

        return Theme::class === $to && null !== ($context['input']['class'] ?? null);
    }
}
