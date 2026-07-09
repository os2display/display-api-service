<?php

declare(strict_types=1);

namespace App\NemDeling;

use App\Entity\Template;
use App\Entity\Tenant;
use App\Entity\Tenant\Playlist;
use App\Entity\Tenant\PlaylistSlide;
use App\Entity\Tenant\Slide;
use App\NemDeling\Exception\NemDelingConfigurationException;
use App\NemDeling\Model\NemDelingResult;
use App\NemDeling\Model\NemDelingSlide;
use App\Repository\PlaylistRepository;
use App\Repository\PlaylistSlideRepository;
use App\Repository\SlideRepository;
use App\Repository\TemplateRepository;
use App\Repository\TenantRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Ulid;

final class NemDelingService
{
    public function __construct(
        private readonly NemDelingConfig $config,
        private readonly TenantRepository $tenantRepository,
        private readonly TemplateRepository $templateRepository,
        private readonly PlaylistRepository $playlistRepository,
        private readonly PlaylistSlideRepository $playlistSlideRepository,
        private readonly SlideRepository $slideRepository,
        private readonly LoggerInterface $logger,
    ) {}

    public function getEventPlaylistFromScreenName(string $screenName, Tenant $tenant): ?Playlist
    {
        return $this->getPlaylistByName(sprintf('event_%s', $screenName), $tenant);
    }

    public function getEventListPlaylistFromScreenName(string $screenName, Tenant $tenant): ?Playlist
    {
        return $this->getPlaylistByName(sprintf('event_list_%s', $screenName), $tenant);
    }

    /**
     * @param array{result: array<string, NemDelingSlide[]>, notFound: string[]} $mappedData
     *
     * @return NemDelingResult[]
     */
    public function syncEvents(array $mappedData): array
    {
        $tenant = $this->resolveTenant();
        $results = [];

        foreach ($mappedData['result'] as $screenName => $slides) {
            $playlist = $this->getEventPlaylistFromScreenName($screenName, $tenant);
            if (!$playlist instanceof Playlist) {
                continue;
            }

            $success = $this->syncPlaylist($playlist, $slides, $tenant);
            $results[] = new NemDelingResult($screenName, $success ? 'success' : 'error');
        }

        foreach ($mappedData['notFound'] as $screenName) {
            $results[] = new NemDelingResult($screenName, 'not_found');
        }

        return $results;
    }

    /**
     * @param array{result: array<string, NemDelingSlide[]>, notFound: string[]} $mappedData
     *
     * @return NemDelingResult[]
     */
    public function syncEventLists(array $mappedData): array
    {
        $tenant = $this->resolveTenant();
        $template = $this->resolveTemplate($this->config->getEventListTemplateTitle());
        $results = [];

        foreach ($mappedData['result'] as $screenName => $slides) {
            $playlist = $this->getEventListPlaylistFromScreenName($screenName, $tenant);
            if (!$playlist instanceof Playlist) {
                continue;
            }

            if ([] === $slides) {
                $success = $this->syncPlaylist($playlist, [], $tenant);
            } else {
                $aggregateSlide = new NemDelingSlide(
                    templateId: (string) $template->getId(),
                    content: [
                        'jsonData' => json_encode(array_map(
                            static fn (NemDelingSlide $slide): array => $slide->content,
                            $slides
                        ), JSON_THROW_ON_ERROR),
                    ],
                    type: 'eventList',
                );
                $success = $this->syncPlaylist($playlist, [$aggregateSlide], $tenant);
            }

            $results[] = new NemDelingResult($screenName, $success ? 'success' : 'error');
        }

        foreach ($mappedData['notFound'] as $screenName) {
            $results[] = new NemDelingResult($screenName, 'not_found');
        }

        return $results;
    }

    /**
     * @param NemDelingSlide[] $slides
     */
    public function syncPlaylist(Playlist $playlist, array $slides, Tenant $tenant): bool
    {
        $playlistId = $playlist->getId();
        if (!$playlistId instanceof Ulid) {
            return false;
        }

        try {
            /** @var PlaylistSlide[] $playlistSlides */
            $playlistSlides = $this->playlistSlideRepository
                ->getPlaylistSlideRelationsFromPlaylistId($playlistId)
                ->getQuery()
                ->getResult();

            $newRelations = [];
            foreach ($slides as $index => $slideDefinition) {
                $newRelations[] = (object) [
                    'slide' => $this->ensureSlideOnPlaylist($slideDefinition, $index, $playlistSlides, $tenant),
                    'weight' => $index,
                ];
            }

            $newSlideIds = array_map(
                static fn (object $relation): string => (string) $relation->slide,
                $newRelations
            );

            foreach ($playlistSlides as $oldRelation) {
                $oldSlide = $oldRelation->getSlide();
                $oldSlideId = (string) $oldSlide->getId();
                if (!in_array($oldSlideId, $newSlideIds, true)) {
                    $this->slideRepository->remove($oldSlide, true);
                }
            }

            $this->playlistSlideRepository->updatePlaylistSlideRelations(
                $playlistId,
                new ArrayCollection($newRelations),
                $tenant
            );

            return true;
        } catch (\Throwable $exception) {
            $this->logger->error(
                sprintf('Error updating playlist "%s"', (string) $playlistId),
                ['exception' => $exception]
            );

            return false;
        }
    }

    public function resolveTenant(): Tenant
    {
        $tenantKey = $this->config->getTenantKey();
        if ('' === $tenantKey) {
            throw new NemDelingConfigurationException('NEMDELING_TENANT_KEY is not configured.');
        }

        $tenant = $this->tenantRepository->findOneBy(['tenantKey' => $tenantKey]);
        if (!$tenant instanceof Tenant) {
            throw new NemDelingConfigurationException(sprintf('Tenant "%s" was not found.', $tenantKey));
        }

        return $tenant;
    }

    public function resolveTemplate(string $title): Template
    {
        $template = $this->templateRepository->findOneBy(['title' => $title]);
        if (!$template instanceof Template) {
            throw new NemDelingConfigurationException(sprintf('Template "%s" was not found.', $title));
        }

        return $template;
    }

    private function getPlaylistByName(string $name, Tenant $tenant): ?Playlist
    {
        return $this->playlistRepository->findOneBy([
            'title' => $name,
            'tenant' => $tenant,
        ]);
    }

    /**
     * @param PlaylistSlide[] $playlistSlides
     */
    private function ensureSlideOnPlaylist(
        NemDelingSlide $slideDefinition,
        int $weight,
        array $playlistSlides,
        Tenant $tenant,
    ): Ulid {
        $existingSlide = $this->findExistingSlide($slideDefinition, $playlistSlides);
        $title = $this->buildSlideTitle($slideDefinition, $weight);

        if ($existingSlide instanceof Slide) {
            if ($this->contentMatches($slideDefinition->content, $existingSlide->getContent())) {
                return $existingSlide->getId();
            }

            $existingSlide
                ->setTitle($title)
                ->setContent($slideDefinition->content);
            $existingSlide->setTemplate($this->resolveTemplateById($slideDefinition->templateId));
            $this->slideRepository->save($existingSlide, true);

            return $existingSlide->getId();
        }

        $slide = new Slide();
        $slide
            ->setTenant($tenant)
            ->setTitle($title)
            ->setContent($slideDefinition->content)
            ->setTemplate($this->resolveTemplateById($slideDefinition->templateId));

        $this->slideRepository->save($slide, true);

        return $slide->getId();
    }

    /**
     * @param PlaylistSlide[] $playlistSlides
     */
    private function findExistingSlide(NemDelingSlide $slideDefinition, array $playlistSlides): ?Slide
    {
        $externalId = $slideDefinition->content['externalId'] ?? '';
        if ('' !== $externalId) {
            foreach ($playlistSlides as $playlistSlide) {
                $slide = $playlistSlide->getSlide();
                if (($slide->getContent()['externalId'] ?? '') === $externalId) {
                    return $slide;
                }
            }
        }

        foreach ($playlistSlides as $playlistSlide) {
            $slide = $playlistSlide->getSlide();
            if ($this->contentMatches($slideDefinition->content, $slide->getContent())) {
                return $slide;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $left
     * @param array<string, mixed> $right
     */
    private function contentMatches(array $left, array $right): bool
    {
        return json_encode($left, JSON_THROW_ON_ERROR) === json_encode($right, JSON_THROW_ON_ERROR);
    }

    private function buildSlideTitle(NemDelingSlide $slideDefinition, int $weight): string
    {
        $title = $slideDefinition->content['title'] ?? 'Event';

        return sprintf('NemDeling Slide - %s - %d', $title, $weight + 1);
    }

    private function resolveTemplateById(string $templateId): Template
    {
        $template = $this->templateRepository->find($templateId);
        if (!$template instanceof Template) {
            throw new NemDelingConfigurationException(sprintf('Template "%s" was not found.', $templateId));
        }

        return $template;
    }
}
