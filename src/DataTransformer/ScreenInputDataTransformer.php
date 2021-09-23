<?php

namespace App\DataTransformer;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Serializer\AbstractItemNormalizer;
use ApiPlatform\Core\Validator\ValidatorInterface;
use App\Dto\ScreenInput;
use App\Entity\Screen;
use App\Repository\ScreenLayoutRepository;

final class ScreenInputDataTransformer implements DataTransformerInterface
{
    private ValidatorInterface $validator;
    private ScreenLayoutRepository $layoutRepository;

    public function __construct(ValidatorInterface $validator, ScreenLayoutRepository $layoutRepository)
    {
        $this->validator = $validator;
        $this->layoutRepository = $layoutRepository;
    }

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
        empty($data->dimensions['width']) ?: $screen->setResolutionWidth((int) $data->dimensions['width']);
        empty($data->dimensions['height']) ?: $screen->setResolutionHeight((int) $data->dimensions['height']);

        // @TODO: Should the regex below contain path and should it be hardcoded.
        if (!empty($data->layout)) {
            // Validate that layout exists path.
            preg_match('@^/v1/layouts/([A-Za-z0-9]{26})$@', $data->layout, $matches);
            if (2 !== count($matches)) {
                throw new InvalidArgumentException('Unknown layout resource');
            }

            // Try loading layout entity.
            $layout = $this->layoutRepository->findOneBy(['id' => end($matches)]);
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
