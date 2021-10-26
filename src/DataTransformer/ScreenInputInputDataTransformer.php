<?php

namespace App\DataTransformer;

use ApiPlatform\Core\Exception\InvalidArgumentException;
use App\Dto\ScreenInput;
use App\Entity\Screen;

final class ScreenInputInputDataTransformer extends AbstractInputDataTransformer
{
    /**
     * {@inheritdoc}
     */
    public function transform($object, string $to, array $context = []): Screen
    {
        $screen = parent::transform($object, $to, $context);

        /* @var ScreenInput $object */
        empty($object->size) ?: $screen->setSize((int) $object->size);
        empty($object->location) ?: $screen->setLocation($object->location);
        empty($object->dimensions['width']) ?: $screen->setResolutionWidth((int) $object->dimensions['width']);
        empty($object->dimensions['height']) ?: $screen->setResolutionHeight((int) $object->dimensions['height']);

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
