<?php

namespace App\EventListener;

use App\Entity\Tenant\Feed;
use App\Exceptions\EntityException;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Contracts\Cache\CacheInterface;

class FeedDoctrineEventListener
{
    public function __construct(
        private CacheInterface $feedsCache
    ) {}

    public function preRemove(Feed $feed, LifecycleEventArgs $event): void
    {
        // On feed remove clear each feed cache entry.
        $this->clearFeedData($feed);
    }

    public function postUpdate(Feed $feed, LifecycleEventArgs $event): void
    {
        // On feed update clear each feed cache entry.
        $this->clearFeedData($feed);
    }

    private function clearFeedData(Feed $feed): void
    {
        $feedId = $feed->getId();

        if (null === $feedId) {
            throw new EntityException('Feed id is null');
        }

        $this->feedsCache->delete($feedId->jsonSerialize());
    }
}
