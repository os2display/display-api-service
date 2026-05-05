<?php

declare(strict_types=1);

namespace App\Feed;

use App\Entity\Tenant\Feed;
use App\Entity\Tenant\FeedSource;
use App\Service\FeedService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Uid\Ulid;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @see https://api.listen.notified.com/docs/index.html
 */
class NotifiedFeedType implements FeedTypeInterface
{
    final public const string SUPPORTED_FEED_TYPE = FeedOutputModels::INSTAGRAM_OUTPUT;

    private const string BASE_URL = 'https://api.listen.notified.com';
    private const string DATETIME_FORMAT = 'Y-m-d\TH:i:s.v\Z';
    private const string LOOKBACK_PERIOD = '-3 months';

    public function __construct(
        private readonly FeedService $feedService,
        private readonly HttpClientInterface $client,
        private readonly LoggerInterface $logger,
    ) {}

    public function getData(Feed $feed): array
    {
        try {
            $secrets = $feed->getFeedSource()?->getSecrets();
            if (!isset($secrets['token'])) {
                throw new \RuntimeException('NotifiedFeedType: Token secret is not set.');
            }

            $configuration = $feed->getConfiguration();
            if (!isset($configuration['feeds']) || 0 === count($configuration['feeds'])) {
                throw new \RuntimeException('NotifiedFeedType: Feeds configuration is not set.');
            }

            $slide = $feed->getSlide();
            $slideContent = $slide?->getContent();

            $pageSize = $slideContent['maxEntries'] ?? 10;

            $token = $secrets['token'];

            $mentions = [];

            try {
                $mentions = $this->getMentions($token, 1, $pageSize, $configuration['feeds']);
            } catch (\Throwable $throwable) {
                $this->logger->error("NotifiedFeedType: Failed to get mentions: {$throwable->getMessage()}");
            }

            $result = [];

            // Check that image/video is available and accessible, otherwise leave out the feed element.
            // Use the content type to determine if the mediaUrl is an image or video.
            // If the content type is not available, try to determine it from the file extension.
            foreach ($mentions as $dataItem) {
                $mediaUrl = $dataItem['mediaUrl'] ?? null;

                if (!is_string($mediaUrl)) {
                    continue;
                }

                try {
                    $response = $this->client->request(Request::METHOD_HEAD, $mediaUrl);
                } catch (\Throwable $throwable) {
                    $this->logger->error("NotifiedFeedType: Failed to get mediaUrl: {$throwable->getMessage()}");
                    continue;
                }

                $statusCode = $response->getStatusCode();

                if ($statusCode >= 200 && $statusCode < 400) {
                    $headers = $response->getHeaders();
                    $contentType = $headers['content-type'][0] ?? null;
                    $imageUrl = null;
                    $videoUrl = null;

                    if (null === $contentType) {
                        $parsedPath = parse_url($mediaUrl, PHP_URL_PATH);

                        if (is_string($parsedPath)) {
                            $ext = strtolower(pathinfo($parsedPath, PATHINFO_EXTENSION));

                            if (in_array($ext, ['mp4', 'mov', 'avi', 'mkv', 'webm', 'flv', 'wmv', 'mpeg', 'mpg', 'm4v', 'ogv', '3gp'])) {
                                $videoUrl = $mediaUrl;
                            } elseif (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg', 'tiff', 'tif', 'ico', 'heic', 'heif', 'avif'])) {
                                $imageUrl = $mediaUrl;
                            } else {
                                continue;
                            }
                        }
                    } elseif (str_starts_with($contentType, 'video/')) {
                        $videoUrl = $mediaUrl;
                    } elseif (str_starts_with($contentType, 'image/')) {
                        $imageUrl = $mediaUrl;
                    }

                    // Skip if no valid media detected
                    if (null === $imageUrl && null === $videoUrl) {
                        continue;
                    }

                    $description = $dataItem['description'] ?? null;

                    $feedItem = [
                        'text' => $description,
                        'textMarkup' => null !== $description ? $this->wrapTags($description) : null,
                        'mediaUrl' => $imageUrl,
                        'videoUrl' => $videoUrl,
                        'username' => $dataItem['sourceName'] ?? null,
                        'createdTime' => $dataItem['published'] ?? null,
                    ];

                    $result[] = $feedItem;
                }
            }

            return $result;
        } catch (\Throwable $throwable) {
            $this->logger->error('{code}: {message}', [
                'code' => $throwable->getCode(),
                'message' => $throwable->getMessage(),
            ]);

            throw $throwable;
        }
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
                'key' => 'notified-selector',
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
     */
    public function getConfigOptions(Request $request, FeedSource $feedSource, string $name): ?array
    {
        try {
            if ('feeds' === $name) {
                $secrets = $feedSource->getSecrets();

                if (!isset($secrets['token'])) {
                    return [];
                }

                $token = $secrets['token'];

                $data = $this->getSearchProfiles($token);

                return array_map(fn (array $item) => [
                    'id' => Ulid::generate(),
                    'title' => $item['name'] ?? '',
                    'value' => $item['id'] ?? '',
                ], $data);
            }
        } catch (\Throwable $throwable) {
            $this->logger->error('{code}: {message}', [
                'code' => $throwable->getCode(),
                'message' => $throwable->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function getMentions(string $token, int $page = 1, int $pageSize = 10, array $searchProfileIds = []): array
    {
        $body = [
            'page' => $page,
            'pageSize' => $pageSize,
            'searchProfileIds' => $searchProfileIds,
            'from' => (new \DateTime(self::LOOKBACK_PERIOD))->format(self::DATETIME_FORMAT),
        ];

        $res = $this->client->request(
            'POST',
            self::BASE_URL.'/api/listen/mentions',
            [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Notified-Custom-Token' => $token,
                ],
                'body' => json_encode($body),
            ]
        );

        return $res->toArray();
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function getSearchProfiles(string $token): array
    {
        $response = $this->client->request(
            'GET',
            self::BASE_URL.'/api/listen/searchprofiles',
            [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Notified-Custom-Token' => $token,
                ],
            ]
        );

        return $response->toArray();
    }

    /**
     * {@inheritDoc}
     */
    public function getRequiredSecrets(): array
    {
        return [
            'token' => [
                'type' => 'string',
            ],
        ];
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

    private function wrapTags(string $input): string
    {
        $text = trim($input);

        // Strip unicode zero-width-space.
        $text = str_replace("\xE2\x80\x8B", '', $text);

        // Collects trailing tags one by one.
        $trailingTags = [];
        $pattern = "/\s*#(?<tag>[^\s#]+)\n?$/u";
        while (preg_match($pattern, (string) $text, $matches)) {
            // We're getting tags in reverse order.
            array_unshift($trailingTags, $matches['tag']);
            $text = preg_replace($pattern, '', (string) $text);
        }

        // Wrap sections in p tags.
        $text = preg_replace("/(.+)\n?/u", '<p>\1</p>', (string) $text);

        // Wrap inline tags.
        $pattern = '/(#(?<tag>[^\s#]+))/';

        return implode('', [
            '<div class="text">',
            preg_replace($pattern, '<span class="tag">\1</span>', (string) $text),
            '</div>',
            '<div class="tags">',
            implode(' ',
                array_map(fn ($tag) => '<span class="tag">#'.$tag.'</span>', $trailingTags)
            ),
            '</div>',
        ]);
    }

    public function getSchema(): array
    {
        return [
            '$schema' => 'http://json-schema.org/draft-04/schema#',
            'type' => 'object',
            'properties' => [
                'token' => [
                    'type' => 'string',
                ],
            ],
            'required' => ['token'],
        ];
    }
}
