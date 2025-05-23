<?php

declare(strict_types=1);

namespace App\Feed;

use App\Entity\Tenant\Feed;
use App\Entity\Tenant\FeedSource;
use App\Feed\OutputModel\ConfigOption;
use App\Feed\SourceType\Brnd\ApiClient;
use App\Feed\SourceType\Brnd\SecretsDTO;
use App\Service\FeedService;
use FeedIo\Feed\Item;
use FeedIo\Feed\Node\Category;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Uid\Ulid;

/**
 * Brnd Bookingsystem Feed.
 *
 * @see https://brndapi.brnd.com/swagger/index.html
 */
class BrndFeedType implements FeedTypeInterface
{
    public const int CACHE_TTL = 3600;

    final public const string SUPPORTED_FEED_TYPE = FeedOutputModels::RSS_OUTPUT;

    public function __construct(
        private readonly FeedService $feedService,
        private readonly ApiClient $apiClient,
        private readonly CacheItemPoolInterface $feedsCache,
    ) {}

    public function getAdminFormOptions(FeedSource $feedSource): array
    {
        $feedEntryRecipients = $this->feedService->getFeedSourceConfigUrl($feedSource, 'sport-center');

        return [
            [
                'key' => 'brnd-sport-center-id',
                'input' => 'input',
                'type' => 'text',
                'name' => 'sport_center_id',
                'label' => 'Sport Center ID',
                'formGroupClasses' => 'mb-3',
            ],
        ];
    }

    public function getData(Feed $feed): array
    {
        $result = [
            'title' => 'BRND Booking',
            'entries' => [],
        ];

        $configuration = $feed->getConfiguration();
        $feedSource = $feed->getFeedSource();

        if (null == $feedSource) {
            return $result;
        }

        $secrets = new SecretsDTO($feedSource);

        $baseUri = $secrets->apiBaseUri;
        $recipients = $configuration['recipients'] ?? [];
        $publishers = $configuration['publishers'] ?? [];
        $pageSize = isset($configuration['page_size']) ? (int) $configuration['page_size'] : 10;

        if (empty($baseUri) || 0 === count($recipients)) {
            return $result;
        }

        $feedSource = $feed->getFeedSource();

        if (null === $feedSource) {
            return $result;
        }

        $entries = $this->apiClient->getFeedEntriesNews($feedSource, $recipients, $publishers, $pageSize);

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

            $updated = $entry->updated ?? $entry->publishDate;
            $item->setLastModified(new \DateTime($updated));

            $author = new Item\Author();
            $author->setName($entry->publisher->name);
            $item->setAuthor($author);

            if (null !== $entry->fields->galleryItems) {
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
                $allowedIds = $feedSource->getSecrets()['allowed_recipients'] ?? [];
                $allGroupOptions = $this->getConfigOptions($request, $feedSource, 'recipients');

                if (null === $allGroupOptions) {
                    return [];
                }

                return array_values(array_filter($allGroupOptions, fn (ConfigOption $group) => in_array($group->value, $allowedIds)));
            case 'recipients':
                $id = self::getIdKey($feedSource);

                /** @var CacheItemInterface $cacheItem */
                $cacheItem = $this->feedsCache->getItem('colibo_feed_entry_groups_'.$id);

                if ($cacheItem->isHit()) {
                    $groups = $cacheItem->get();
                } else {
                    $groups = $this->apiClient->getSearchGroups($feedSource);

                    $groups = array_map(fn (array $item) => new ConfigOption(
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
                'type' => 'string',
            ],
            'client_secret' => [
                'type' => 'string',
            ],
            'allowed_recipients' => [
                'type' => 'string_array',
                'exposeValue' => true,
            ],
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
            'required' => ['api_base_uri', 'client_id', 'client_secret'],
        ];
    }

    public static function getIdKey(FeedSource $feedSource): string
    {
        $ulid = $feedSource->getId();
        assert(null !== $ulid);

        return $ulid->toBase32();
    }
}
