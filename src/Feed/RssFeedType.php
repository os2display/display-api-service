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

    public function getData(Feed $feed): ?array
    {
        $configuration = $feed->getConfiguration();
        $numberOfEntries = $configuration['numberOfEntries'] ?? null;

        $feedResult = $this->feedIo->read($configuration['url']);

        $result = [
            'title' => $feedResult->getFeed()->getTitle(),
            'entries' => [],
        ];

        /** @var Item $item */
        foreach ($feedResult->getFeed() as $item) {
            $result['entries'][] = $item->toArray();

            if (!is_null($numberOfEntries) && count($result['entries']) >= $numberOfEntries) {
                break;
            }
        }

        return $result;
    }

    public function getAdminFormOptions(FeedSource $feedSource): ?array
    {
        // @TODO: Translation.
        return [
            [
                'key' => 'rss-url',
                'input' => 'input',
                'name' => 'url',
                'type' => 'url',
                'label' => 'Kilde',
                'helpText' => 'Her kan du skrive rss kilden',
                'formGroupClasses' => 'col-md-6',
            ],
            [
                'key' => 'rss-number-of-entries',
                'input' => 'input',
                'name' => 'numberOfEntries',
                'type' => 'number',
                'label' => 'Antal indgange',
                'helpText' => 'Her kan du skrive, hvor mange indgange, der maksimalt skal vises.',
                'formGroupClasses' => 'col-md-6 mb-3',
            ],
            [
                'key' => 'rss-entry-duration',
                'input' => 'input',
                'name' => 'entryDuration',
                'type' => 'number',
                'label' => 'Varighed pr. indgang (i sekunder)',
                'helpText' => 'Her skal du skrive varigheden pr. indgang.',
                'formGroupClasses' => 'col-md-6 mb-3',
            ],
        ];
    }

    public function getConfigOptions(FeedSource $feedSource, string $name): ?array
    {
        return null;
    }
}
