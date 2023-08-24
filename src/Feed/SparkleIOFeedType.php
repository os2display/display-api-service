<?php

namespace App\Feed;

use App\Entity\Tenant\Feed;
use App\Entity\Tenant\FeedSource;
use App\Exceptions\MissingFeedConfigurationException;
use App\Service\FeedService;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Uid\Ulid;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SparkleIOFeedType implements FeedTypeInterface
{
    public const SUPPORTED_FEED_TYPE = 'instagram';
    public const REQUEST_TIMEOUT = 10;

    public function __construct(
        private FeedService $feedService,
        private HttpClientInterface $client,
        private AdapterInterface $feedsCache
    ) {}

    /**
     * @param Feed $feed
     *
     * @return array
     *
     * @throws ClientExceptionInterface
     * @throws MissingFeedConfigurationException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws \JsonException
     */
    public function getData(Feed $feed): array
    {
        $secrets = $feed->getFeedSource()?->getSecrets();
        if (!isset($secrets['baseUrl']) || !isset($secrets['clientId']) || !isset($secrets['clientSecret'])) {
            throw new MissingFeedConfigurationException('baseUrl, clientId and clientSecret secrets should be set');
        }

        $configuration = $feed->getConfiguration();
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
        $data = json_decode($contents, false, 512, JSON_THROW_ON_ERROR);

        $res = [];
        foreach ($data->items as $item) {
            $res[] = $this->getFeedItemObject($item);
        }

        return $res;
    }

    /**
     * {@inheritDoc}
     */
    public function getAdminFormOptions(FeedSource $feedSource): array
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

    /**
     * {@inheritDoc}
     *
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws \JsonException
     */
    public function getConfigOptions(Request $request, FeedSource $feedSource, string $name): ?array
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

    /**
     * {@inheritDoc}
     */
    public function getRequiredSecrets(): array
    {
        return ['baseUrl', 'clientId', 'clientSecret'];
    }

    /**
     * {@inheritDoc}
     */
    public function getRequiredConfiguration(): array
    {
        return ['feeds'];
    }

    /**
     * {@inheritDoc}
     */
    public function getSupportedFeedOutputType(): string
    {
        return self::SUPPORTED_FEED_TYPE;
    }

    /**
     * Get oAuth token.
     *
     * @param string $baseUrl
     * @param string $clientId
     * @param string $clientSecret
     *
     * @return string
     *
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws \JsonException|InvalidArgumentException
     */
    private function getToken(string $baseUrl, string $clientId, string $clientSecret): string
    {
        /** @var CacheItemInterface $cacheItem */
        $cacheItem = $this->feedsCache->getItem('sparkleio-token');

        if ($cacheItem->isHit()) {
            /** @var string $token */
            $token = $cacheItem->get();
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
                ]
            );

            $content = $response->getContent();
            $contentDecoded = json_decode($content, false, 512, JSON_THROW_ON_ERROR);

            $token = $contentDecoded->access_token;
            $expireSeconds = intval($contentDecoded->expires_in / 1000 - 30);

            $cacheItem->set($token);
            $cacheItem->expiresAfter($expireSeconds);
        }

        return $token;
    }

    /**
     * Parse feed item into object.
     *
     * @param object $item
     *
     * @return object
     */
    private function getFeedItemObject(object $item): object
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

    /**
     * @param string $input
     *
     * @return string
     */
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
}
