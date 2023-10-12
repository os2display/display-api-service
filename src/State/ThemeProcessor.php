<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Serializer\AbstractItemNormalizer;
use App\Dto\ThemeInput;
use App\Entity\Tenant\Theme;
use App\Exceptions\DataTransformerException;
use App\Repository\MediaRepository;
use App\Repository\ThemeRepository;
use App\Utils\IriHelperUtils;
use Doctrine\ORM\EntityManagerInterface;

class ThemeProcessor extends AbstractProcessor
{
    public function __construct(
        private readonly IriHelperUtils $iriHelperUtils,
        private readonly MediaRepository $mediaRepository,
        private readonly ThemeRepository $themeRepository,
        EntityManagerInterface $entityManager
    ) {
        parent::__construct($entityManager);
    }

    protected function fromInput(mixed $object, Operation $operation, array $uriVariables, array $context): Theme
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
}
