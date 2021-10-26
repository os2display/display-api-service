<?php

namespace App\DataTransformer;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Serializer\AbstractItemNormalizer;
use App\Dto\SlideInput;
use App\Entity\Media;
use App\Entity\Slide;
use App\Repository\MediaRepository;
use App\Repository\TemplateRepository;
use App\Utils\IriHelperUtils;
use App\Utils\ValidationUtils;

final class SlideInputInputDataTransformer extends AbstractInputDataTransformer
{
    public function __construct(
        protected ValidationUtils $utils,
        private IriHelperUtils $iriHelperUtils,
        private TemplateRepository $templateRepository,
        private MediaRepository $mediaRepository
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function transform($object, string $to, array $context = []): Slide
    {
        $slide = parent::transform($object, $to, $context);

        /* @var SlideInput $object */
        empty($object->duration) ?: $slide->setDuration($object->duration);
        empty($object->templateInfo['options']) ?: $slide->setTemplateOptions($object->templateInfo['options']);
        empty($object->content) ?: $slide->setContent($object->content);

        if (!empty($object->templateInfo['@id'])) {
            // Validate that template IRI exists.
            $ulid = $this->iriHelperUtils->getUlidFromIRI($object->templateInfo['@id']);

            // Try loading layout entity.
            $template = $this->templateRepository->findOneBy(['id' => $ulid]);
            if (is_null($template)) {
                throw new InvalidArgumentException('Unknown template resource');
            }

            $slide->setTemplate($template);
        }

        $slide->removeAllMedium();
        foreach ($object->media as $mediaIri) {
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
