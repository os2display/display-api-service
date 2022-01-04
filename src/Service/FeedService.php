<?php

namespace App\Service;

use ApiPlatform\Core\Api\OperationType;
use ApiPlatform\Core\Bridge\Symfony\Routing\RouteNameGenerator;
use App\Entity\Feed;
use App\Entity\FeedSource;
use App\Feed\FeedTypeInterface;
use Psr\Cache\CacheItemInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Cache\CacheInterface;

class FeedService
{
    public function __construct(private iterable $feedTypes, private CacheInterface $cache, private UrlGeneratorInterface $urlGenerator)
    {
    }

    public function getAdmin(FeedSource $feedSource): ?array
    {
        /** @var FeedTypeInterface $feedType */
        foreach ($this->feedTypes as $feedType) {
            if ($feedType::class === $feedSource->getFeedType()) {
                return $feedType->getAdmin($feedSource);
            }
        }

        return [];
    }

    public function getFeedTypes(): array
    {
        $res = [];

        foreach ($this->feedTypes as $feedType) {
            $res[] = $feedType::class;
        }

        return $res;
    }

    public function getFeedUrl(Feed $feed): string
    {
        // @TODO: Find solution without depending on @internal RouteNameGenerator for generating route name.
        $routeName = RouteNameGenerator::generate('feed_data', 'Feed', OperationType::ITEM);

        return $this->urlGenerator->generate($routeName, ['id' => $feed->getId()]);
    }

    public function getFeedSourceConfigUrl(FeedSource $feedSource, $name): string
    {
        // @TODO: Find solution without depending on @internal RouteNameGenerator for generating route name.
        $routeName = RouteNameGenerator::generate('feed_source_config', 'FeedSource', OperationType::ITEM);

        return $this->urlGenerator->generate($routeName, ['id' => $feedSource->getId(), 'name' => $name], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    public function getFeedConfiguration(Feed $feed): array
    {
        $feedSourceConfiguration = $feed->getFeedSource()->getConfiguration();
        $feedConfiguration = $feed->getConfiguration();

        return array_merge($feedSourceConfiguration, $feedConfiguration);
    }

    public function getData(Feed $feed): ?array
    {
        /** @var CacheItemInterface $cacheItem */
        $cacheItem = $this->cache->getItem($feed->getId()->jsonSerialize());

        if ($cacheItem->isHit()) {
            return $cacheItem->get();
        } else {
            $feedSource = $feed->getFeedSource();
            $feedTypeClassName = $feedSource->getFeedType();
            $feedConfiguration = $this->getFeedConfiguration($feed);

            foreach ($this->feedTypes as $feedType) {
                if ($feedType::class === $feedTypeClassName) {
                    $data = $feedType->getData($feed);

                    $cacheItem->set($data);

                    if (isset($feedConfiguration['cache_expire'])) {
                        $cacheItem->expiresAfter($feedConfiguration['cache_expire']);
                    }

                    $this->cache->save($cacheItem);

                    return $data;
                }
            }

            return null;
        }
    }

    public function getConfigOptions(FeedSource $feedSource, string $name): ?array
    {
        $feedTypeClassName = $feedSource->getFeedType();

        foreach ($this->feedTypes as $feedType) {
            if ($feedType::class === $feedTypeClassName) {
                return $feedType->getConfigOptions($feedSource, $name);
            }
        }

        return null;
    }
}
