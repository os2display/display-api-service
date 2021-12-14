<?php

namespace App\Service;

use ApiPlatform\Core\Api\OperationType;
use ApiPlatform\Core\Api\UrlGeneratorInterface;
use ApiPlatform\Core\Bridge\Symfony\Routing\RouteNameGenerator;
use App\Entity\Feed;
use App\Event\GetFeedTypesEvent;
use App\Feed\FeedTypeInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class FeedService
{
    public function __construct(private EventDispatcherInterface $dispatcher, private UrlGeneratorInterface $urlGenerator) {}

    public function getFeedTypes(): array
    {
        $event = new GetFeedTypesEvent();
        $event = $this->dispatcher->dispatch($event, GetFeedTypesEvent::NAME);
        return $event->getFeedTypes();
    }

    public function getFeedUrl(Feed $feed): string
    {
        $routeName = RouteNameGenerator::generate('getFeedData', 'feed', OperationType::ITEM);
        return $this->urlGenerator->generate($routeName, ['id' => $feed->getId()]);
    }

    public function getData(Feed $feed): array
    {
        // @TODO: Check for cached result.

        $feedSource = $feed->getFeedSource();
        $feedTypeClassName = $feedSource->getFeedType();

        /** @var FeedTypeInterface $feedTypeInstance */
        $feedTypeInstance = new $feedTypeClassName();

        return $feedTypeInstance->getData($feedSource, $feed);
    }
}
