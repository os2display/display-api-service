<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Tenant\Feed;
use App\Entity\Tenant\FeedSource;
use App\Exceptions\UnknownFeedTypeException;
use App\Feed\FeedTypeInterface;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class FeedService
{
    public function __construct(
        private readonly iterable $feedTypes,
        private readonly CacheItemPoolInterface $feedsCache,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {}

    /**
     * @return array|null
     */
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

    /**
     * Get remote feed url.
     *
     * @return string
     */
    public function getRemoteFeedUrl(Feed $feed): string
    {
        // Cf. operation definition in config/api_platform/feed.yaml
        $routeName = '_api_Feed_get_data';

        return $this->urlGenerator->generate($routeName, ['id' => $feed->getId()]);
    }

    /**
     * Get feed source url.
     *
     * @return string
     */
    public function getFeedSourceConfigUrl(FeedSource $feedSource, string $name): string
    {
        // Cf. operation definition in config/api_platform/feed_source.yaml
        $routeName = '_api_Feed_get_source_config';

        return $this->urlGenerator->generate($routeName, ['id' => $feedSource->getId(), 'name' => $name], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    /**
     * Get feed data (feed items).
     *
     * @param Feed $feed
     *   The feed to fetch data for
     *
     * @return array|null
     *   Array with feed data
     */
    public function getData(Feed $feed): ?array
    {
        // Get feed id.
        $feedId = $feed->getId()?->jsonSerialize();

        if (is_null($feedId)) {
            return null;
        }

        /** @var CacheItemInterface $cacheItem */
        $cacheItem = $this->feedsCache->getItem($feedId);

        if (false && $cacheItem->isHit()) {
            /** @var array $data */
            $data = $cacheItem->get();
        } else {
            $feedSource = $feed->getFeedSource();
            $feedTypeClassName = $feedSource?->getFeedType();
            $feedConfiguration = $feed->getConfiguration();

            /** @var FeedTypeInterface $feedType */
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

    /**
     * Get feed type based on class name.
     *
     * @return FeedTypeInterface
     *
     * @throws UnknownFeedTypeException
     */
    public function getFeedType(string $className): FeedTypeInterface
    {
        foreach ($this->feedTypes as $feedType) {
            if ($className == $feedType::class) {
                return $feedType;
            }
        }

        throw new UnknownFeedTypeException(sprintf('Unknown feed type from "%s" class', $className));
    }

    /**
     * Get configuration options based on feed source.
     *
     * @return array|null
     */
    public function getConfigOptions(Request $request, FeedSource $feedSource, string $name): ?array
    {
        $feedTypeClassName = $feedSource->getFeedType();

        /** @var FeedTypeInterface $feedType */
        foreach ($this->feedTypes as $feedType) {
            if ($feedType::class === $feedTypeClassName) {
                return $feedType->getConfigOptions($request, $feedSource, $name);
            }
        }

        return null;
    }
}
