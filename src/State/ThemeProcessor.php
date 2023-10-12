<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Serializer\AbstractItemNormalizer;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\ThemeInput;
use App\Entity\Tenant\Theme;
use App\Exceptions\DataTransformerException;
use App\Repository\MediaRepository;
use App\Repository\ThemeRepository;
use App\Utils\IriHelperUtils;

class ThemeProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly IriHelperUtils $iriHelperUtils,
        private readonly MediaRepository $mediaRepository,
        private readonly ThemeRepository $themeRepository,
    ) {}

    /**
     * {@inheritdoc}
     *
     * @param ThemeInput $object
     */
    public function process(mixed $object, Operation $operation, array $uriVariables = [], array $context = [])
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

        $this->themeRepository->save($theme, true);

        return $theme;
    }
}
