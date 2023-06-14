<?php

namespace App\Service;

use ApiPlatform\Core\Api\OperationType;
use ApiPlatform\Core\Bridge\Symfony\Routing\RouteNameGenerator;
use App\Entity\Tenant\Feed;
use App\Entity\Tenant\FeedSource;
use App\Feed\FeedTypeInterface;
use Psr\Cache\CacheItemInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Cache\CacheInterface;

class FeedService
{
    public function __construct(
        private iterable $feedTypes,
        private CacheInterface $feedsCache,
        private UrlGeneratorInterface $urlGenerator
    ) {}

    public function getAdminFormOptions(FeedSource $feedSource): ?array
    {
        /** @var FeedTypeInterface $feedType */
        foreach ($this->feedTypes as $feedType) {
            if ($feedType::class === $feedSource->getFeedType()) {
                return $feedType->getAdminFormOptions($feedSource);
            }
        }

        return [];
    }

    public function getFeedType(string $className): ?FeedTypeInterface
    {
        foreach ($this->feedTypes as $feedType) {
            if ($className == $feedType::class) {
                return $feedType;
            }
        }

        return null;
    }

    /**
     * Get class names for defined feed types in the system.
     *
     * @return array
     *   Array with feed type class names
     */
    public function getFeedTypes(): array
    {
        $res = [];

        foreach ($this->feedTypes as $feedType) {
            $res[] = $feedType::class;
        }

        return $res;
    }

    public function getRemoteFeedUrl(Feed $feed): string
    {
        // @TODO: Find solution without depending on @internal RouteNameGenerator for generating route name.
        $routeName = RouteNameGenerator::generate('get_feed_data', 'Feed', OperationType::ITEM);

        return $this->urlGenerator->generate($routeName, ['id' => $feed->getId()]);
    }

    public function getFeedSourceConfigUrl(FeedSource $feedSource, $name): string
    {
        // @TODO: Find solution without depending on @internal RouteNameGenerator for generating route name.
        $routeName = RouteNameGenerator::generate('feed_source_config', 'FeedSource', OperationType::ITEM);

        return $this->urlGenerator->generate($routeName, ['id' => $feedSource->getId(), 'name' => $name], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    /**
     * Get feed data (feed items).
     *
     * @param feed $feed
     *   The feed to fetch data for
     *
     * @return array|null
     *   Array with feed data
     */
    public function getData(Feed $feed): ?array
    {
        // Get feed id.
        $feedId = $feed->getId()->jsonSerialize();

        /** @var CacheItemInterface $cacheItem */
        $cacheItem = $this->feedsCache->getItem($feedId);

        if ($cacheItem->isHit()) {
            /** @var array $data */
            $data = $cacheItem->get();
        } else {
            $feedSource = $feed->getFeedSource();
            $feedTypeClassName = $feedSource->getFeedType();
            $feedConfiguration = $feed->getConfiguration();

            foreach ($this->feedTypes as $feedType) {
                if ($feedType::class === $feedTypeClassName) {
                    $data = $feedType->getData($feed);

                    $cacheItem->set($data);
                    if (isset($feedConfiguration['cache_expire'])) {
                        $cacheItem->expiresAfter($feedConfiguration['cache_expire']);
                    }
                    $this->feedsCache->save($cacheItem);

                    return $data;
                }
            }

            // If feed type was not known in the system return null. API platform will convert this to 404 not found.
            return null;
        }

        return $data;
    }

    public function getConfigOptions(Request $request, FeedSource $feedSource, string $name): array|\stdClass|null
    {
        $feedTypeClassName = $feedSource->getFeedType();

        foreach ($this->feedTypes as $feedType) {
            if ($feedType::class === $feedTypeClassName) {
                return $feedType->getConfigOptions($request, $feedSource, $name);
            }
        }

        return null;
    }
}
