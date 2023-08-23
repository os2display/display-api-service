<?php

namespace App\DataTransformer;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use ApiPlatform\Core\Serializer\AbstractItemNormalizer;
use App\Dto\ThemeInput;
use App\Entity\Tenant\Theme;
use App\Exceptions\DataTransformerException;
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
    public function transform($object, string $to, array $context = []): Theme
    {
        $theme = new Theme();
        if (array_key_exists(AbstractItemNormalizer::OBJECT_TO_POPULATE, $context)) {
            $theme = $context[AbstractItemNormalizer::OBJECT_TO_POPULATE];
        }

        /* @var ThemeInput $object */
        empty($object->title) ?: $theme->setTitle($object->title);
        empty($object->description) ?: $theme->setDescription($object->description);
        empty($object->createdBy) ?: $theme->setCreatedBy($object->createdBy);
        empty($object->modifiedBy) ?: $theme->setModifiedBy($object->modifiedBy);
        empty($object->css) ?: $theme->setCssStyles($object->css);

        $theme->removeLogo();
        if (!empty($object->logo)) {
            // Validate that media IRI exists.
            $ulid = $this->iriHelperUtils->getUlidFromIRI($object->logo);

            // Try loading logo entity.
            $logo = $this->mediaRepository->findOneBy(['id' => $ulid]);

            if (is_null($logo)) {
                throw new DataTransformerException('Unknown media resource');
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
