<?php

declare(strict_types=1);

namespace App\Feed\SourceType\Colibo;

use App\Entity\Tenant\Feed;
use App\Entity\Tenant\FeedSource;
use App\Feed\FeedOutputModels;
use App\Feed\FeedTypeInterface;
use App\Feed\OutputModel\ConfigOption;
use App\Service\FeedService;
use FeedIo\Feed\Item;
use FeedIo\Feed\Node\Category;
use Psr\Cache\CacheItemInterface;
use Symfony\Component\BrowserKit\Exception\JsonException;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Uid\Ulid;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Colibo Intranet Feed.
 *
 * @see https://intranet.colibo.com/apidocs
 * @see https://intranet.colibo.com/apidocs/reference/index
 */
class ColiboFeedType implements FeedTypeInterface
{
    public const int CACHE_TTL = 3600;

    final public const string SUPPORTED_FEED_TYPE = FeedOutputModels::RSS_OUTPUT;

    public function __construct(
        private readonly FeedService $feedService,
        private readonly ApiClient $apiClient,
        private readonly CacheInterface $feedsCache,
    ) {}

    public function getAdminFormOptions(FeedSource $feedSource): array
    {
        $feedEntryRecipients = $this->feedService->getFeedSourceConfigUrl($feedSource, 'allowed-recipients');

        return [
            [
                'key' => 'colibo-feed-type-recipient-selector',
                'input' => 'multiselect-from-endpoint',
                'endpoint' => $feedEntryRecipients,
                'name' => 'recipients',
                'label' => 'Grupper',
                'helpText' => 'Vælg hvilke grupper, der skal hentes nyheder fra.',
                'formGroupClasses' => 'mb-3',
            ],
            [
                'key' => 'colibo-feed-type-page-size',
                'input' => 'input',
                'type' => 'number',
                'name' => 'page_size',
                'label' => 'Antal nyheder',
                'defaultValue' => '5',
                'helpText' => 'Vælg hvor mange nyheder der maksimalt skal hentes.',
                'formGroupClasses' => 'mb-3',
            ],
        ];
    }

    public function getData(Feed $feed): array
    {
        $configuration = $feed->getConfiguration();
        $baseUri = $feed->getFeedSource()->getSecrets()['api_base_uri'];

        $result = [
            'title' => 'Intranet',
            'entries' => [],
        ];

        $recipients = $configuration['recipients'] ?? [];
        $publishers = $configuration['publishers'] ?? [];
        $pageSize = isset($configuration['page_size']) ? (int) $configuration['page_size'] : 10;

        if (empty($recipients)) {
            return $result;
        }

        $entries = $this->apiClient->getFeedEntriesNews($feed->getFeedSource(), $recipients, $publishers, $pageSize);

        foreach ($entries as $entry) {
            $item = new Item();
            $item->setTitle($entry->fields->title);

            $crawler = new Crawler($entry->fields->description);
            $summary = '';
            foreach ($crawler as $domElement) {
                $summary .= $domElement->textContent;
            }
            $item->setSummary($summary);

            $item->setPublicId((string) $entry->id);

            $link = sprintf('%s/feedentry/%s', $baseUri, $entry->id);
            $item->setLink($link);

            if (null !== $entry->fields->body) {
                $crawler = new Crawler($entry->fields->body);
                $content = '';
                foreach ($crawler as $domElement) {
                    $content .= $domElement->textContent;
                }
            } else {
                $content = $item->getSummary();
            }
            $item->setContent($content);

            $updated = null === $entry->updated ? $entry->publishDate : $entry->updated;
            $item->setLastModified(new \DateTime($updated));

            $author = new Item\Author();
            $author->setName($entry->publisher->name);
            $item->setAuthor($author);

            if ($entry->fields->galleryItems !== null) {
                try {
                    $galleryItems = json_decode($entry->fields->galleryItems, true, 512, JSON_THROW_ON_ERROR);
                } catch (\JsonException) {
                    $galleryItems = [];
                }

                foreach ($galleryItems as $galleryItem) {
                    $media = new Item\Media();

                    $large = sprintf('%s/api/files/%s/thumbnail/large', $baseUri, $galleryItem['id']);
                    $media->setUrl($large);

                    $small = sprintf('%s/api/files/%s/thumbnail/small', $baseUri, $galleryItem['id']);
                    $media->setThumbnail($small);

                    $item->addMedia($media);
                }
            }

            foreach ($entry->recipients as $recipient) {
                $category = new Category();
                $category->setLabel($recipient->name);

                $item->addCategory($category);
            }

            $result['entries'][] = $item->toArray();
        }

        return $result;
    }

    public function getConfigOptions(Request $request, FeedSource $feedSource, string $name): ?array
    {
        switch ($name) {
            case 'allowed-recipients':
                $allowedIds =  $feedSource->getSecrets()['allowed_recipients'] ?? [];
                $allGroupOptions = $this->getConfigOptions($request, $feedSource, 'recipients');

                return array_values(array_filter($allGroupOptions, fn(ConfigOption $group) => in_array($group->value, $allowedIds)));
            case 'recipients':
                $id = self::getIdKey($feedSource);

                /** @var CacheItemInterface $cacheItem */
                $cacheItem = $this->feedsCache->getItem('colibo_feed_entry_groups_'.$id);

                if ($cacheItem->isHit()) {
                    $groups = $cacheItem->get();
                } else {
                    $groups = $this->apiClient->getSearchGroups($feedSource);

                    $groups = array_map(fn (array $item) =>
                        new ConfigOption(
                            Ulid::generate(),
                            sprintf('%s (%d)', $item['model']['title'], $item['model']['id']),
                            (string) $item['model']['id']
                        ), $groups);

                    usort($groups, fn ($a, $b) => strcmp($a->title, $b->title));

                    $cacheItem->set($groups);
                    $cacheItem->expiresAfter(self::CACHE_TTL);
                    $this->feedsCache->save($cacheItem->set($groups));
                }

                return $groups;
            default:
                return null;
        }
    }

    public function getRequiredSecrets(): array
    {
        return [
            'api_base_uri' => [
                'type' => 'string',
                'exposeValue' => true,
            ],
            'client_id' => [
                'type' => 'string'
            ],
            'client_secret' => [
                'type' => 'string'
            ],
            'allowed_recipients' => [
                'type' => 'string_array',
                'exposeValue' => true,
            ]
        ];
    }

    public function getRequiredConfiguration(): array
    {
        return ['recipients', 'page_size'];
    }

    public function getSupportedFeedOutputType(): string
    {
        return self::SUPPORTED_FEED_TYPE;
    }

    public function getSchema(): array
    {
        return [
            '$schema' => 'http://json-schema.org/draft-04/schema#',
            'type' => 'object',
            'properties' => [
                'api_base_uri' => [
                    'type' => 'string',
                    'format' => 'uri',
                ],
                'client_id' => [
                    'type' => 'string',
                ],
                'client_secret' => [
                    'type' => 'string',
                ],
                'allowed_recipients' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'string',
                    ],
                ],
            ],
            'required' => ['api_base_uri', 'client_id', 'client_secret', 'allowed_recipients'],
        ];
    }

    public static function getIdKey(FeedSource $feedSource): string
    {
        $ulid = $feedSource->getId();
        assert(null !== $ulid);

        return $ulid->toBase32();
    }
}
