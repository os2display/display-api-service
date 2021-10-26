<?php

namespace App\DataTransformer;

use App\Dto\Template as TemplateDTO;
use App\Entity\Template;

class TemplateOutputDataTransformer extends AbstractOutputDataTransformer
{
    /**
     * {@inheritdoc}
     */
    public function transform($template, string $to, array $context = []): TemplateDTO
    {
        /** @var Template $template */
        $output = parent::transform($template, $to, $context);
        $output->title = $template->getTitle();
        $output->description = $template->getDescription();
        $output->modified = $template->getUpdatedAt();
        $output->created = $template->getCreatedAt();
        $output->modifiedBy = $template->getModifiedBy();
        $output->createdBy = $template->getCreatedBy();
        $output->resources = $template->getResources();

        return $output;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        return TemplateDTO::class === $to && $data instanceof Template;
    }
}
