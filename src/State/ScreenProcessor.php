<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Serializer\AbstractItemNormalizer;
use App\Dto\ScreenInput;
use App\Entity\Tenant\Screen;
use App\Repository\ScreenLayoutRepository;
use App\Utils\IriHelperUtils;
use Doctrine\ORM\EntityManagerInterface;

class ScreenProcessor extends AbstractProcessor
{
    public function __construct(
        private IriHelperUtils $iriHelperUtils,
        private ScreenLayoutRepository $layoutRepository,
        EntityManagerInterface $entityManager
    ) {
        parent::__construct($entityManager);
    }

    protected function fromInput(mixed $object, Operation $operation, array $uriVariables, array $context): Screen
    {
        $screen = new Screen();
        if (array_key_exists(AbstractItemNormalizer::OBJECT_TO_POPULATE, $context)) {
            $screen = $context[AbstractItemNormalizer::OBJECT_TO_POPULATE];
        }

        assert($object instanceof ScreenInput);
        empty($object->title) ?: $screen->setTitle($object->title);
        empty($object->description) ?: $screen->setDescription($object->description);
        empty($object->createdBy) ?: $screen->setCreatedBy($object->createdBy);
        empty($object->modifiedBy) ?: $screen->setModifiedBy($object->modifiedBy);
        empty($object->size) ?: $screen->setSize((int) $object->size);
        empty($object->location) ?: $screen->setLocation($object->location);
        empty($object->orientation) ?: $screen->setOrientation($object->orientation);
        empty($object->resolution) ?: $screen->setResolution($object->resolution);

        if (isset($object->enableColorSchemeChange)) {
            $screen->setEnableColorSchemeChange($object->enableColorSchemeChange);
        }

        if (!empty($object->layout)) {
            // Validate that layout IRI exists.
            $ulid = $this->iriHelperUtils->getUlidFromIRI($object->layout);

            // Try loading layout entity.
            $layout = $this->layoutRepository->findOneBy(['id' => $ulid]);
            if (is_null($layout)) {
                throw new InvalidArgumentException('Unknown layout resource');
            }

            $screen->setScreenLayout($layout);
        }

        return $screen;
    }
}
