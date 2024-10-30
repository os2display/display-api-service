<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\SlideInput;
use App\Entity\Tenant\Feed;
use App\Entity\Tenant\Slide;
use App\Repository\FeedRepository;
use App\Repository\FeedSourceRepository;
use App\Repository\MediaRepository;
use App\Repository\TemplateRepository;
use App\Repository\ThemeRepository;
use App\Utils\IriHelperUtils;
use App\Utils\ValidationUtils;
use Doctrine\ORM\EntityManagerInterface;

class SlideProcessor extends AbstractProcessor
{
    public function __construct(
        private readonly ValidationUtils $utils,
        private readonly IriHelperUtils $iriHelperUtils,
        private readonly TemplateRepository $templateRepository,
        private readonly ThemeRepository $themeRepository,
        private readonly MediaRepository $mediaRepository,
        private readonly FeedRepository $feedRepository,
        private readonly FeedSourceRepository $feedSourceRepository,
        EntityManagerInterface $entityManager,
        ProcessorInterface $persistProcessor,
        ProcessorInterface $removeProcessor,
        SlideProvider $provider,
    ) {
        parent::__construct($entityManager, $persistProcessor, $removeProcessor, $provider);
    }

    protected function fromInput(mixed $object, Operation $operation, array $uriVariables, array $context): Slide
    {
        // FIXME Do we really have to do (something like) this to load an existing object into the entity manager?
        $slide = $this->loadPrevious(new Slide(), $context);

        if (!$slide instanceof Slide) {
            throw new \InvalidArgumentException('object must by of type Slide.');
        }

        /* @var SlideInput $object */
        empty($object->title) ?: $slide->setTitle($object->title);
        empty($object->description) ?: $slide->setDescription($object->description);
        empty($object->createdBy) ?: $slide->setCreatedBy($object->createdBy);
        empty($object->modifiedBy) ?: $slide->setModifiedBy($object->modifiedBy);
        empty($object->duration) ?: $slide->setDuration($object->duration);

        if (null === $object->published['from']) {
            $slide->setPublishedFrom(null);
        } elseif (!empty($object->published['from'])) {
            $slide->setPublishedFrom($this->utils->validateDate($object->published['from']));
        }

        if (null === $object->published['to']) {
            $slide->setPublishedTo(null);
        } elseif (!empty($object->published['to'])) {
            $slide->setPublishedTo($this->utils->validateDate($object->published['to']));
        }

        empty($object->templateInfo['options']) ?: $slide->setTemplateOptions($object->templateInfo['options']);
        empty($object->content) ?: $slide->setContent($object->content);

        if (!empty($object->templateInfo['@id'])) {
            // Validate that template IRI exists.
            $ulid = $this->iriHelperUtils->getUlidFromIRI($object->templateInfo['@id']);

            // Try loading layout entity.
            $template = $this->templateRepository->findOneBy(['id' => $ulid]);
            if (is_null($template)) {
                throw new \InvalidArgumentException('Unknown template resource');
            }

            $slide->setTemplate($template);
        }

        if (!empty($object->theme)) {
            // Validate that theme IRI exists.
            $ulid = $this->iriHelperUtils->getUlidFromIRI($object->theme);

            // Try loading theme entity.
            $theme = $this->themeRepository->findOneBy(['id' => $ulid]);
            if (is_null($theme)) {
                throw new \InvalidArgumentException('Unknown theme resource');
            }

            $slide->setTheme($theme);
        }

        $slide->removeAllMedium();
        foreach ($object->media as $mediaIri) {
            // Validate that template IRI exists.
            $ulid = $this->iriHelperUtils->getUlidFromIRI($mediaIri);

            // Try loading media entity.
            $media = $this->mediaRepository->findOneBy(['id' => $ulid]);
            if (is_null($media)) {
                throw new \InvalidArgumentException('Unknown media resource');
            }

            $slide->addMedium($media);
        }

        if (!empty($object->feed)) {
            $feedData = $object->feed;

            $feed = null;

            if (!empty($feedData['@id'])) {
                $feed = $this->feedRepository->find($feedData['@id']);
            }

            if (!$feed) {
                $feed = new Feed();
                $slide->setFeed($feed);
            }

            if (!empty($feedData['feedSource'])) {
                $feedUlid = $this->iriHelperUtils->getUlidFromIRI($feedData['feedSource']);
                $feedSource = $this->feedSourceRepository->find($feedUlid);

                if (is_null($feedSource)) {
                    throw new \InvalidArgumentException('Unknown feedSource resource');
                }

                $feed->setFeedSource($feedSource);
            }

            empty($feedData['configuration']) ?: $feed->setConfiguration($feedData['configuration']);
        }

        return $slide;
    }
}
