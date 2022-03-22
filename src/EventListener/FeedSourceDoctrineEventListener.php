<?php

namespace App\EventListener;

use App\Entity\Tenant\FeedSource;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Contracts\Cache\CacheInterface;

class FeedSourceDoctrineEventListener
{
    public function __construct(private CacheInterface $feedsCache)
    {
    }

    public function preRemove(FeedSource $feedSource, LifecycleEventArgs $event): void
    {
        // On feed source remove clear each feed cache entry.
        $this->clearFeedData($feedSource);
    }

    public function postUpdate(FeedSource $feedSource, LifecycleEventArgs $event): void
    {
        // On feed source update clear each feed cache entry.
        $this->clearFeedData($feedSource);
    }

    private function clearFeedData(FeedSource $feedSource): void
    {
        $feeds = $feedSource->getFeeds();

        foreach ($feeds as $feed) {
            $this->feedsCache->delete($feed->getId()->jsonSerialize());
        }
    }
}
