<?php

namespace App\Service;

use App\Entity\Feed;
use App\Event\GetFeedTypesEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class FeedService
{
    public function __construct(private EventDispatcherInterface $dispatcher) {}

    public function getFeedTypes(): array
    {
        $event = new GetFeedTypesEvent();
        $event = $this->dispatcher->dispatch($event, GetFeedTypesEvent::NAME);
        return $event->getFeedTypes();
    }

    public function getFeedUrl(Feed $feed): string
    {
        // @TODO: Generate feed url.
        return $feed->getId();
    }
}
