<?php

namespace App\Service;

use ApiPlatform\Core\Api\OperationType;
use ApiPlatform\Core\Api\UrlGeneratorInterface;
use ApiPlatform\Core\Bridge\Symfony\Routing\RouteNameGenerator;
use App\Entity\Feed;

class FeedService
{
    public function __construct(private iterable $feedTypes, private UrlGeneratorInterface $urlGenerator)
    {
    }

    public function getFeedTypes(): array
    {
        $res = $this->feedTypes->toArray();

        foreach ($this->feedTypes as $feedType) {
            $res[] = $feedType::class;
        }

        return $res;
    }

    public function getFeedUrl(Feed $feed): string
    {
        // @TODO: Find solution without depending on @internal RouteNameGenerator.
        $routeName = RouteNameGenerator::generate('getFeedData', 'feed', OperationType::ITEM);

        return $this->urlGenerator->generate($routeName, ['id' => $feed->getId()]);
    }

    public function getData(Feed $feed): ?array
    {
        // @TODO: Check for cached result.

        $feedSource = $feed->getFeedSource();
        $feedTypeClassName = $feedSource->getFeedType();

        foreach ($this->feedTypes as $feedType) {
            if ($feedType::class === $feedTypeClassName) {
                return $feedType->getData($feedSource, $feed);
            }
        }

        return null;
    }
}
