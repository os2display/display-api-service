<?php

namespace App\DataTransformer;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use ApiPlatform\Core\Serializer\AbstractItemNormalizer;
use ApiPlatform\Core\Validator\ValidatorInterface;
use App\Dto\ScreenGroupInput;
use App\Entity\ScreenGroup;

final class ScreenGroupInputDataTransformer implements DataTransformerInterface
{
    private ValidatorInterface $validator;

    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($data, string $to, array $context = []): ScreenGroup
    {
        $screenGroup = new ScreenGroup();
        if (array_key_exists(AbstractItemNormalizer::OBJECT_TO_POPULATE, $context)) {
            $screenGroup = $context[AbstractItemNormalizer::OBJECT_TO_POPULATE];
        }

        /* @var ScreenGroupInput $data */
        empty($data->title) ?: $screenGroup->setTitle($data->title);
        empty($data->description) ?: $screenGroup->setDescription($data->title);
        empty($data->createdBy) ?: $screenGroup->setCreatedBy($data->title);
        empty($data->modifiedBy) ?: $screenGroup->setModifiedBy($data->title);

        return $screenGroup;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        if ($data instanceof ScreenGroup) {
            return false;
        }

        return ScreenGroup::class === $to && null !== ($context['input']['class'] ?? null);
    }
}
