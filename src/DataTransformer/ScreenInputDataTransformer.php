<?php

namespace App\DataTransformer;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Serializer\AbstractItemNormalizer;
use App\Dto\ScreenInput;
use App\Entity\Tenant\Screen;
use App\Repository\ScreenLayoutRepository;
use App\Utils\IriHelperUtils;

final class ScreenInputDataTransformer implements DataTransformerInterface
{
    public function __construct(
        private IriHelperUtils $iriHelperUtils,
        private ScreenLayoutRepository $layoutRepository
    ) {}

    /**
     * {@inheritdoc}
     */
    public function transform($data, string $to, array $context = []): Screen
    {
        $screen = new Screen();
        if (array_key_exists(AbstractItemNormalizer::OBJECT_TO_POPULATE, $context)) {
            $screen = $context[AbstractItemNormalizer::OBJECT_TO_POPULATE];
        }

        /* @var ScreenInput $data */
        empty($data->title) ?: $screen->setTitle($data->title);
        empty($data->description) ?: $screen->setDescription($data->description);
        empty($data->createdBy) ?: $screen->setCreatedBy($data->createdBy);
        empty($data->modifiedBy) ?: $screen->setModifiedBy($data->modifiedBy);
        empty($data->size) ?: $screen->setSize((int) $data->size);
        empty($data->location) ?: $screen->setLocation($data->location);
        empty($data->orientation) ?: $screen->setOrientation($data->orientation);
        empty($data->resolution) ?: $screen->setResolution($data->resolution);

        if (isset($data->enableColorSchemeChange)) {
            $screen->setEnableColorSchemeChange($data->enableColorSchemeChange);
        }

        if (!empty($data->layout)) {
            // Validate that layout IRI exists.
            $ulid = $this->iriHelperUtils->getUlidFromIRI($data->layout);

            // Try loading layout entity.
            $layout = $this->layoutRepository->findOneBy(['id' => $ulid]);
            if (is_null($layout)) {
                throw new InvalidArgumentException('Unknown layout resource');
            }

            $screen->setScreenLayout($layout);
        }

        return $screen;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        if ($data instanceof Screen) {
            return false;
        }

        return Screen::class === $to && null !== ($context['input']['class'] ?? null);
    }
}
