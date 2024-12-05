<?php

declare(strict_types=1);

namespace App\Feed\SourceType\Colibo;

use App\Entity\Tenant\Feed;
use App\Entity\Tenant\FeedSource;
use App\Feed\FeedOutputModels;
use App\Feed\FeedTypeInterface;
use App\Service\FeedService;
use FeedIo\Feed\Item;
use FeedIo\Feed\Node\Category;
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
        $feedEntryPublishers = $this->feedService->getFeedSourceConfigUrl($feedSource, 'FeedEntryPublishers');
        $feedEntryRecipients = $this->feedService->getFeedSourceConfigUrl($feedSource, 'FeedEntryRecipients');

        // @TODO: Translation.
        return [
            [
                'key' => 'colibo-feed-entry-publishers-selector',
                'input' => 'multiselect-from-endpoint',
                'endpoint' => $feedEntryPublishers,
                'name' => 'colibo-feed-entry-publishers',
                'label' => 'Vælg afsender grupper for de nyheder du ønsker at vise',
                'helpText' => 'Her vælger du hvilke afsender grupper der skal hentes nyheder fra.',
                'formGroupClasses' => 'col-md-6 mb-3',
            ],
            [
                'key' => 'colibo-feed-entry-recipients-selector',
                'input' => 'multiselect-from-endpoint',
                'endpoint' => $feedEntryRecipients,
                'name' => 'colibo-feed-entry-recipients',
                'label' => 'Vælg modtager grupper for de nyheder du ønsker at vise',
                'helpText' => 'Her vælger du hvilke afsender grupper der skal hentes nyheder fra.',
                'formGroupClasses' => 'col-md-6 mb-3',
            ],
        ];
    }

    public function getData(Feed $feed): array
    {
        $configuration = $feed->getConfiguration();
        $baseUri = $feed->getFeedSource()->getSecrets()['api_base_uri'];

        $entries = $this->apiClient->getFeedEntriesNews($feed->getFeedSource(), $configuration['colibo-feed-entry-recipients'], $configuration['colibo-feed-entry-publishers']);

        $result = [
            'title' => 'Colibo Feed',
            'entries' => [],
        ];

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
                $galleryItems = json_decode($entry->fields->galleryItems, true, 512, JSON_THROW_ON_ERROR);
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
            case 'FeedEntryPublishers':
            case 'FeedEntryRecipients':
                $id = self::getIdKey($feedSource);

                /** @var CacheItemInterface $cacheItem */
                $cacheItem = $this->feedsCache->getItem('colibo_feed_entry_publishers_groups_'.$id);

                if ($cacheItem->isHit()) {
                    $groups = $cacheItem->get();
                } else {
                    $groups = $this->apiClient->getSearchGroups($feedSource);

                    $groups = array_map(fn (array $item) => [
                        'id' => Ulid::generate(),
                        'title' => sprintf('%s (%d)', $item['model']['title'], $item['model']['id']),
                        'value' => (string) $item['model']['id'],
                    ], $groups);

                    usort($groups, fn ($a, $b) => strcmp($a['title'], $b['title']));

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
        return ['client_id', 'client_secret'];
    }

    public function getRequiredConfiguration(): array
    {
        return ['api_base_uri'];
    }

    public function getSupportedFeedOutputType(): string
    {
        return self::SUPPORTED_FEED_TYPE;
    }

    public function getSchema(): array
    {
        return [];
    }

    public static function getIdKey(FeedSource $feedSource): string
    {
        $ulid = $feedSource->getId();
        assert(null !== $ulid);

        return $ulid->toBase32();
    }
}
