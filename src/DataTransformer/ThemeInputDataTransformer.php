<?php

namespace App\DataTransformer;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use ApiPlatform\Core\Serializer\AbstractItemNormalizer;
use App\Dto\ThemeInput;
use App\Entity\Tenant\Theme;

final class ThemeInputDataTransformer implements DataTransformerInterface
{
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
        empty($data->css) ?: $theme->setCssStyles($data->css);

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
