<?php

namespace App\Feed;

use App\Entity\Feed;
use App\Entity\FeedSource;
use App\Service\FeedService;
use Psr\Cache\CacheItemInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Uid\Ulid;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SparkleIOFeedType implements FeedTypeInterface
{
    public const REQUEST_TIMEOUT = 10;

    public function __construct(private FeedService $feedService, private HttpClientInterface $client, private CacheInterface $feedsCache)
    {
    }

    public function getAdminFormOptions(FeedSource $feedSource): ?array
    {
        $endpoint = $this->feedService->getFeedSourceConfigUrl($feedSource, 'feeds');

        // @TODO: Translation.
        return [
            [
                'key' => 'sparkle-io-selector',
                'input' => 'multiselect-from-endpoint',
                'endpoint' => $endpoint,
                'name' => 'feeds',
                'label' => 'Vælg feed',
                'helpText' => 'Her vælger du hvilket feed der skal hentes indgange fra.',
                'formGroupClasses' => 'col-md-6 mb-3',
            ],
        ];
    }

    public function getData(Feed $feed): array|\stdClass|null
    {
        $secrets = $feed->getFeedSource()->getSecrets();

        if (!isset($secrets['baseUrl']) || !isset($secrets['clientId']) || !isset($secrets['clientSecret'])) {
            throw new \Exception('baseUrl, clientId and clientSecret secrets should be set');
        }

        $configuration = $this->feedService->getFeedConfiguration($feed);

        if (!isset($configuration['feeds']) || 0 === count($configuration['feeds'])) {
            return [];
        }

        $baseUrl = $secrets['baseUrl'];
        $clientId = $secrets['clientId'];
        $clientSecret = $secrets['clientSecret'];

        $token = $this->getToken($baseUrl, $clientId, $clientSecret);

        $res = $this->client->request(
            'GET',
            $baseUrl.'v0.1/feed/'.$configuration['feeds'][0],
            [
                'timeout' => self::REQUEST_TIMEOUT,
                'headers' => [
                    'Authorization' => sprintf('Bearer %s', $token),
                ],
            ]
        );

        $contents = $res->getContent();

        $arr = json_decode($contents);

        $res = [];

        foreach ($arr->items as $item) {
            $res[] = $this->getFeedItemObject($item);
        }

        return $res;
    }

    public function getConfigOptions(Request $request, FeedSource $feedSource, string $name): array|\stdClass|null
    {
        if ('feeds' === $name) {
            $secrets = $feedSource->getSecrets();

            if (!isset($secrets['baseUrl']) || !isset($secrets['clientId']) || !isset($secrets['clientSecret'])) {
                return [];
            }

            $baseUrl = $secrets['baseUrl'];
            $clientId = $secrets['clientId'];
            $clientSecret = $secrets['clientSecret'];

            $token = $this->getToken($baseUrl, $clientId, $clientSecret);

            $response = $this->client->request(
                'GET',
                $baseUrl.'v0.1/feed',
                [
                    'timeout' => self::REQUEST_TIMEOUT,
                    'headers' => [
                        'Authorization' => sprintf('Bearer %s', $token),
                    ],
                ]
            );

            $contents = $response->getContent();

            $items = json_decode($contents);

            $feeds = [];

            foreach ($items as $item) {
                $feeds[] = [
                    'id' => Ulid::generate(),
                    'title' => $item->name ?? '',
                    'value' => $item->id ?? '',
                ];
            }

            return $feeds;
        }

        return null;
    }

    private function getToken($baseUrl, $clientId, $clientSecret)
    {
        /** @var CacheItemInterface $cacheItem */
        $cacheItem = $this->feedsCache->getItem('sparkleio-token');

        if ($cacheItem->isHit()) {
            return $cacheItem->get();
        } else {
            $response = $this->client->request(
                'POST',
                $baseUrl.'oauth/token',
                [
                    'timeout' => self::REQUEST_TIMEOUT,
                    'headers' => [
                        'Content-Type' => 'application/x-www-form-urlencoded',
                    ],
                    'body' => [
                        'grant_type' => urlencode('client_credentials'),
                        'client_id' => urlencode($clientId),
                        'client_secret' => urlencode($clientSecret),
                    ],
                ]);

            $content = $response->getContent();
            $contentDecoded = json_decode($content);
            $token = $contentDecoded->access_token;
            $expireSeconds = intval($contentDecoded->expires_in / 1000 - 30);

            $cacheItem->set($token);
            $cacheItem->expiresAfter($expireSeconds);

            return $token;
        }
    }

    private function getFeedItemObject($item): object
    {
        return (object) [
            'text' => $item->text,
            'textMarkup' => null !== $item->text ? $this->wrapTags($item->text) : null,
            'mediaUrl' => $item->mediaUrl,
            'videoUrl' => $item->videoUrl,
            'username' => $item->username,
            'createdTime' => $item->createdTime,
        ];
    }

    private function wrapTags(string $input): string
    {
        $text = trim($input);

        // Strip unicode zero-width-space.
        $text = str_replace("\xE2\x80\x8B", '', $text);

        // Collects trailing tags one by one.
        $trailingTags = [];
        $pattern = "/\s*#(?<tag>[^\s#]+)\n?$/u";
        while (preg_match($pattern, $text, $matches)) {
            // We're getting tags in reverse order.
            array_unshift($trailingTags, $matches['tag']);
            $text = preg_replace($pattern, '', $text);
        }

        // Wrap sections in p tags.
        $text = preg_replace("/(.+)\n?/u", '<p>\1</p>', $text);

        // Wrap inline tags.
        $pattern = '/(#(?<tag>[^\s#]+))/';
        $text = '<div class="text">'.preg_replace($pattern,
                '<span class="tag">\1</span>', $text).'</div>';
        // Append tags.
        $text .= PHP_EOL.'<div class="tags">'.implode(' ',
                array_map(function ($tag) {
                    return '<span class="tag">#'.$tag.'</span>';
                }, $trailingTags)).'</div>';

        return $text;
    }

    public function getRequiredSecrets(): array
    {
        return ['baseUrl', 'clientId', 'clientSecret'];
    }

    public function getRequiredConfiguration(): array
    {
        return ['feeds'];
    }
}
