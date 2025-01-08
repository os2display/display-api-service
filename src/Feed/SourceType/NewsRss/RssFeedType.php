<?php

declare(strict_types=1);

namespace App\Feed\SourceType\NewsRss;

use App\Entity\Tenant\Feed;
use App\Entity\Tenant\FeedSource;
use App\Feed\FeedOutputModels;
use App\Feed\FeedTypeInterface;
use App\Feed\OutputModel\News\News;
use App\Feed\OutputModel\News\NewsOutput;
use FeedIo\Adapter\Http\Client;
use FeedIo\Feed\Item;
use FeedIo\Feed\Node\CategoryInterface;
use FeedIo\FeedIo;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\HttplugClient;
use Symfony\Component\HttpFoundation\Request;

class RssFeedType implements FeedTypeInterface
{
    final public const string SUPPORTED_FEED_TYPE = FeedOutputModels::NEWS_OUTPUT;

    private readonly FeedIo $feedIo;

    public function __construct(
        private readonly LoggerInterface $logger,
    )
    {
        $client = new Client(new HttplugClient());
        $this->feedIo = new FeedIo($client, $this->logger);
    }

    /**
     * Get data from the feed-.
     *
     * @param Feed $feed
     *   Feed object
     *
     * @return array
     *   Array with title and feed entities
     */
    public function getData(Feed $feed): array
    {
        try {
            $configuration = $feed->getConfiguration();
            $numberOfEntries = $configuration['numberOfEntries'] ?? null;
            $url = $configuration['url'] ?? null;

            if (!isset($url)) {
                return [];
            }

            $results = [];
            $feedResult = $this->feedIo->read($url);

            /** @var Item $item */
            foreach ($feedResult->getFeed() as $item) {
                $medias = $item->getMedias();

                $results[] = new News(
                    array_map(fn (CategoryInterface $category) => $category->getLabel(), iterator_to_array($item->getCategories())),
                    $item->getTitle(),
                    strip_tags($item->getContent() ?? ''),
                    $item->getSummary(),
                    count($medias) > 0 ? $medias[0]->getUrl() : null,
                    $item->getAuthor()?->getName(),
                    $item->getLastModified()->format('c'),
                    $feedResult->getFeed()->getTitle(),
                    $item->getLink(),
                );

                if (!is_null($numberOfEntries) && count($results) >= $numberOfEntries) {
                    break;
                }
            }

            return (new NewsOutput($results))->toArray();
        } catch (\Throwable $throwable) {
            $this->logger->error($throwable->getCode() . ': ' . $throwable->getMessage());
        }

        return [];
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

    public function getSchema(): array
    {
        return [
            '$schema' => 'http://json-schema.org/draft-04/schema#',
            'type' => 'object',
        ];
    }
}
