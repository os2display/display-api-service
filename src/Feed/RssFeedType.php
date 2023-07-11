<?php

namespace App\Feed;

use App\Entity\Tenant\Feed;
use App\Entity\Tenant\FeedSource;
use App\Exceptions\MissingFeedConfigurationException;
use FeedIo\Factory;
use FeedIo\Feed\Item;
use FeedIo\FeedIo;
use Symfony\Component\HttpFoundation\Request;

class RssFeedType implements FeedTypeInterface
{
    public const SUPPORTED_FEED_TYPE = 'rss';
    private FeedIo $feedIo;

    public function __construct()
    {
        $this->feedIo = Factory::create()->getFeedIo();
    }

    /**
     * Get data from the feed-.
     *
     * @param feed $feed
     *   Feed object
     *
     * @return array
     *   Array with title and feed entities
     *
     * @throws MissingFeedConfigurationException
     */
    public function getData(Feed $feed): array
    {
        $configuration = $feed->getConfiguration();
        $numberOfEntries = $configuration['numberOfEntries'] ?? null;
        $url = $configuration['url'] ?? null;

        if (!isset($url)) {
            throw new MissingFeedConfigurationException('URL not configured');
        }

        $feedResult = $this->feedIo->read($url);
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

    /**
     * {@inheritDoc}
     */
    public function getAdminFormOptions(FeedSource $feedSource): array
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

    /**
     * {@inheritDoc}
     */
    public function getConfigOptions(Request $request, FeedSource $feedSource, string $name): ?array
    {
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function getRequiredSecrets(): array
    {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function getRequiredConfiguration(): array
    {
        return ['url'];
    }

    /**
     * {@inheritDoc}
     */
    public function getSupportedFeedOutputType(): string
    {
        return self::SUPPORTED_FEED_TYPE;
    }
}
