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
    public function __construct(private iterable $feedTypes, private CacheInterface $feedsCache, private UrlGeneratorInterface $urlGenerator)
    {
    }

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

    public function getFeedType(string $className): ?FeedTypeInterface {
        foreach ($this->feedTypes as $feedType) {
            if ($className == $feedType::class) {
                return $feedType;
            }
        }
        return null;
    }

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
        $routeName = RouteNameGenerator::generate('feed_data', 'Feed', OperationType::ITEM);

        return $this->urlGenerator->generate($routeName, ['id' => $feed->getId()]);
    }

    public function getFeedSourceConfigUrl(FeedSource $feedSource, $name): string
    {
        // @TODO: Find solution without depending on @internal RouteNameGenerator for generating route name.
        $routeName = RouteNameGenerator::generate('feed_source_config', 'FeedSource', OperationType::ITEM);

        return $this->urlGenerator->generate($routeName, ['id' => $feedSource->getId(), 'name' => $name], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    public function getData(Feed $feed): array|\stdClass|null
    {
        /** @var CacheItemInterface $cacheItem */
        $cacheItem = $this->feedsCache->getItem($feed->getId()->jsonSerialize());

        if ($cacheItem->isHit()) {
            return $cacheItem->get();
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

            return null;
        }
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
