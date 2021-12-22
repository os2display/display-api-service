<?php

namespace App\Feed;

use App\Entity\Feed;
use App\Entity\FeedSource;
use FeedIo\Factory;
use FeedIo\Feed\Item;
use FeedIo\FeedIo;

class RssFeedType implements FeedTypeInterface
{
    private FeedIo $feedIo;

    public function __construct()
    {
        $this->feedIo = Factory::create()->getFeedIo();
    }

    public function getData(FeedSource $feedSource, Feed $feed): ?array
    {
        $configuration = $feed->getConfiguration();

        $feedResult = $this->feedIo->read($configuration['url']);

        $result = [
            'title' => $feedResult->getFeed()->getTitle(),
            'entries' => [],
        ];

        /** @var Item $item */
        foreach ($feedResult->getFeed() as $item) {
            $result['entries'][] = $item->toArray();
        }

        return $result;
    }

    public function getAdmin(): ?array
    {
        // @TODO: Translation.
        return [
            [
                'key' => 'rss-src',
                'input' => 'input',
                'name' => 'source',
                'type' => 'url',
                'label' => 'Kilde',
                'helpText' => 'Her kan du skrive rss kilden',
                'formGroupClasses' => 'col-md-6',
            ]
        ];
    }
}
