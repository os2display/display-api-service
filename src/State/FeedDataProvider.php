<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Tenant\Feed;
use App\Entity\Tenant\FeedData;
use App\Entity\User;
use App\Repository\FeedRepository;
use App\Repository\PlaylistSlideRepository;
use App\Service\FeedService;
use Doctrine\ORM\NonUniqueResultException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Uid\Ulid;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;

/**
 * A FeedData state provider.
 *
 * @see https://api-platform.com/docs/v2.7/core/state-providers/
 *
 * @template T of FeedData
 */
final class FeedDataProvider extends AbstractProvider
{
    public function __construct(
        private readonly Security $security,
        private readonly PlaylistSlideRepository $playlistSlideRepository,
        private readonly FeedRepository $feedRepository,
        private readonly FeedService $feedService,
        private readonly LoggerInterface $logger,
        private readonly iterable $itemExtensions,
        ProviderInterface $collectionProvider
    ) {
        parent::__construct($collectionProvider, $this->feedRepository);
    }

    public function toOutput(object $object): object
    {
        assert($object instanceof Feed);

        $output = new \App\Dto\Feed();
        $output->id = $object->getId();
        $output->created = $object->getCreatedAt();
        $output->modified = $object->getModifiedAt();
        $output->createdBy = $object->getCreatedBy();
        $output->modifiedBy = $object->getModifiedBy();
        $output->setRelationsModified($object->getRelationsModified());

        $output->configuration = $object->getConfiguration();
        $output->slide = $object->getSlide();
        $output->feedSource = $object->getFeedSource();

        return $output;
    }

    protected function provideItem(Operation $operation, array $uriVariables = [], array $context = []): ?object
    {
        $resourceClass = Feed::class;
        $id = $uriVariables['id'] ?? null;
        if (!$id instanceof Ulid) {
            return null;
        }

        $queryNameGenerator = new QueryNameGenerator();

        // Create a query builder, as the tenant filter works on query builders.
        $queryBuilder = $this->feedRepository->getById($id);

        $identifiers = ['id' => $id];

        // Filter the query builder with tenant extension.
        foreach ($this->itemExtensions as $extension) {
            if ($extension instanceof QueryItemExtensionInterface) {
                $extension->applyToItem($queryBuilder, $queryNameGenerator, $resourceClass, $identifiers, $operation, $context);
            }
        }

        // Get result. If there is a result this is returned.
        try {
            $feed = $queryBuilder->getQuery()->getOneOrNullResult();
        } catch (NonUniqueResultException) {
            return null;
        }

        // If there is not a result, shared playlists should be checked.
        if (is_null($feed)) {
            $user = $this->security->getUser();
            if (!is_null($user)) {
                /** @var User $user */
                $tenant = $user->getActiveTenant();

                $notAccessibleFeed = $this->feedRepository->find($id);
                if (!is_null($notAccessibleFeed)) {
                    $slide = $notAccessibleFeed->getSlide();
                    $slideId = $slide?->getId();
                    if (!is_null($slideId)) {
                        $playlists = $this->playlistSlideRepository->getPlaylistsFromSlideId($slideId)->getQuery()->getResult();
                        foreach ($playlists as $playlist) {
                            if (in_array($tenant, $playlist->getPlaylist()->getTenants()->toArray())) {
                                $feed = $notAccessibleFeed;
                                break;
                            }
                        }
                    }
                }
            }
        }

        if (!is_null($feed)) {
            $feedId = $feed->getId();
            $feedIdReference = null === $feedId ? '0' : $feedId->jsonSerialize();
            try {
                return new JsonResponse($this->feedService->getData($feed), \Symfony\Component\HttpFoundation\Response::HTTP_OK);
            } catch (MissingFeedConfigurationException $e) {
                $this->logger->error(sprintf('Missing configuration for feed with id "%s" with message "%s"', $feedIdReference, $e->getMessage()));
            } catch (\JsonException $e) {
                $this->logger->error(sprintf('JSON decode for feed with id "%s" with error "%s"', $feedIdReference, $e->getMessage()));
            } catch (ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|TransportExceptionInterface $e) {
                $this->logger->error(sprintf('Communication error "%s"', $e->getMessage()));
            } catch (\Throwable $e) {
                $this->logger->error(sprintf('Feed data error. ID: %s, MESSAGE: %s', $feed->getId()->jsonSerialize(), $e->getMessage()));
            }
        }

        // Null returned for data provider will result in a 404 response from API platform.
        return null;
    }
}
