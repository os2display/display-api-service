<?php

namespace App\DataTransformer;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Serializer\AbstractItemNormalizer;
use App\Dto\SlideInput;
use App\Entity\Slide;
use App\Repository\MediaRepository;
use App\Repository\TemplateRepository;
use App\Repository\ThemeRepository;
use App\Utils\IriHelperUtils;
use App\Utils\ValidationUtils;

final class SlideInputDataTransformer implements DataTransformerInterface
{
    public function __construct(
        private ValidationUtils $utils,
        private IriHelperUtils $iriHelperUtils,
        private TemplateRepository $templateRepository,
        private ThemeRepository $themeRepository,
        private MediaRepository $mediaRepository
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function transform($data, string $to, array $context = []): Slide
    {
        $slide = new Slide();
        if (array_key_exists(AbstractItemNormalizer::OBJECT_TO_POPULATE, $context)) {
            $slide = $context[AbstractItemNormalizer::OBJECT_TO_POPULATE];
        }

        /* @var SlideInput $data */
        empty($data->title) ?: $slide->setTitle($data->title);
        empty($data->description) ?: $slide->setDescription($data->description);
        empty($data->createdBy) ?: $slide->setCreatedBy($data->createdBy);
        empty($data->modifiedBy) ?: $slide->setModifiedBy($data->modifiedBy);
        empty($data->duration) ?: $slide->setDuration($data->duration);

        if (null === $data->published['from']) {
            $slide->setPublishedFrom(null);
        } elseif (!empty($data->published['from'])) {
            $slide->setPublishedFrom($this->utils->validateDate($data->published['from']));
        }

        if (null === $data->published['to']) {
            $slide->setPublishedTo(null);
        } elseif (!empty($data->published['to'])) {
            $slide->setPublishedTo($this->utils->validateDate($data->published['to']));
        }

        empty($data->templateInfo['options']) ?: $slide->setTemplateOptions($data->templateInfo['options']);
        empty($data->content) ?: $slide->setContent($data->content);

        if (!empty($data->templateInfo['@id'])) {
            // Validate that template IRI exists.
            $ulid = $this->iriHelperUtils->getUlidFromIRI($data->templateInfo['@id']);

            // Try loading layout entity.
            $template = $this->templateRepository->findOneBy(['id' => $ulid]);
            if (is_null($template)) {
                throw new InvalidArgumentException('Unknown template resource');
            }

            $slide->setTemplate($template);
        }

        if (!empty($data->theme)) {
            // Validate that theme IRI exists.
            $ulid = $this->iriHelperUtils->getUlidFromIRI($data->theme);

            // Try loading theme entity.
            $theme = $this->themeRepository->findOneBy(['id' => $ulid]);
            if (is_null($theme)) {
                throw new InvalidArgumentException('Unknown theme resource');
            }

            $slide->setTheme($theme);
        }

        $slide->removeAllMedium();
        foreach ($data->media as $mediaIri) {
            // Validate that template IRI exists.
            $ulid = $this->iriHelperUtils->getUlidFromIRI($mediaIri);

            // Try loading media entity.
            $media = $this->mediaRepository->findOneBy(['id' => $ulid]);
            if (is_null($media)) {
                throw new InvalidArgumentException('Unknown media resource');
            }

            $slide->addMedium($media);
        }

        return $slide;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        if ($data instanceof Slide) {
            return false;
        }

        return Slide::class === $to && null !== ($context['input']['class'] ?? null);
    }
}
