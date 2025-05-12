<?php

declare(strict_types=1);

namespace App\Feed\SourceType\Colibo;

use App\Entity\Tenant\FeedSource;
use App\Feed\ColiboFeedType;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class ApiClient
{
    private const int BATCH_SIZE = 500;
    private const string GRANT_TYPE = 'client_credentials';
    private const string SCOPE = 'api FeedEntries.Read.All';

    /** @var array<string, HttpClientInterface> */
    private array $apiClients = [];

    public function __construct(
        private readonly CacheItemPoolInterface $feedsCache,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Get Feed News Entries for a given FeedSource.
     *
     * @param FeedSource $feedSource
     *   The FeedSource to scope by
     * @param array $recipients
     *   An array of recipient ID's to filter by
     * @param array $publishers
     *   An array of publisher ID's to filter by
     * @param int $pageSize
     *   Number of elements to retrieve
     *
     * @return mixed
     */
    public function getFeedEntriesNews(FeedSource $feedSource, array $recipients = [], array $publishers = [], int $pageSize = 10): mixed
    {
        try {
            $client = $this->getApiClient($feedSource);

            $options = [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'query' => [
                    'recipients' => array_map(fn ($recipient) => (object) [
                        'Id' => $recipient,
                        'Type' => 'Group',
                    ], $recipients),
                    'publishers' => array_map(fn ($publisher) => (object) [
                        'Id' => $publisher,
                        'Type' => 'Group',
                    ], $publishers),
                    'pageSize' => $pageSize,
                ],
            ];

            $response = $client->request('GET', '/api/feedentries/news', $options);

            return json_decode($response->getContent(), false, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $throwable) {
            $this->logger->error('{code}: {message}', [
                'code' => $throwable->getCode(),
                'message' => $throwable->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Retrieve search groups based on the given feed source and type.
     *
     * @param FeedSource $feedSource
     * @param string $type
     *
     * @return array
     */
    public function getSearchGroups(FeedSource $feedSource, string $type = 'Department'): array
    {
        try {
            $responseData = $this->getSearchGroupsPage($feedSource, $type)->toArray();

            $groups = $responseData['results'];

            $total = $responseData['total'];
            $pages = (int) ceil($total / self::BATCH_SIZE);

            /** @var ResponseInterface[] $responses */
            $responses = [];
            for ($page = 1; $page < $pages; ++$page) {
                $responses[] = $this->getSearchGroupsPage($feedSource, $type, $page);
            }

            foreach ($responses as $response) {
                $responseData = $response->toArray();
                $groups = array_merge($groups, $responseData['results']);
            }

            return $groups;
        } catch (\Throwable $throwable) {
            $this->logger->error('{code}: {message}', [
                'code' => $throwable->getCode(),
                'message' => $throwable->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * @param FeedSource $feedSource
     * @param string $type
     * @param int $pageIndex
     * @param int $pageSize
     *
     * @return ResponseInterface
     *
     * @throws ColiboException
     */
    private function getSearchGroupsPage(FeedSource $feedSource, string $type, int $pageIndex = 0, int $pageSize = self::BATCH_SIZE): ResponseInterface
    {
        try {
            $client = $this->getApiClient($feedSource);

            return $client->request('GET', '/api/search/groups', [
                'query' => [
                    'groupSearchQuery.groupTypes' => $type,
                    'groupSearchQuery.pageIndex' => $pageIndex,
                    'groupSearchQuery.pageSize' => $pageSize,
                ],
            ]);
        } catch (ColiboException $exception) {
            throw $exception;
        } catch (\Throwable $throwable) {
            throw new ColiboException($throwable->getMessage(), (int) $throwable->getCode(), $throwable);
        }
    }

    /**
     * Get an authenticated scoped API client for the given FeedSource.
     *
     * @param FeedSource $feedSource
     *
     * @return HttpClientInterface
     *
     * @throws ColiboException
     */
    private function getApiClient(FeedSource $feedSource): HttpClientInterface
    {
        $id = ColiboFeedType::getIdKey($feedSource);

        if (array_key_exists($id, $this->apiClients)) {
            return $this->apiClients[$id];
        }

        $secrets = new SecretsDTO($feedSource);
        $this->apiClients[$id] = HttpClient::createForBaseUri($secrets->apiBaseUri)->withOptions([
            'headers' => [
                'Authorization' => 'Bearer '.$this->fetchToken($feedSource),
                'Accept' => 'application/json',
            ],
        ]);

        return $this->apiClients[$id];
    }

    /**
     * Get the auth token for the given FeedSource.
     *
     * @param FeedSource $feedSource
     *
     * @return string
     *
     * @throws ColiboException
     */
    private function fetchToken(FeedSource $feedSource): string
    {
        $id = ColiboFeedType::getIdKey($feedSource);

        /** @var CacheItemInterface $cacheItem */
        $cacheItem = $this->feedsCache->getItem('colibo_token_'.$id);

        if ($cacheItem->isHit()) {
            /** @var string $token */
            $token = $cacheItem->get();
        } else {
            try {
                $secrets = new SecretsDTO($feedSource);
                $client = HttpClient::createForBaseUri($secrets->apiBaseUri);

                $response = $client->request('POST', '/auth/oauth2/connect/token', [
                    'headers' => [
                        'Content-Type' => 'application/x-www-form-urlencoded',
                        'Accept' => 'application/json',
                    ],
                    'body' => [
                        'grant_type' => self::GRANT_TYPE,
                        'scope' => self::SCOPE,
                        'client_id' => $secrets->clientId,
                        'client_secret' => $secrets->clientSecret,
                    ],
                ]);

                $content = $response->getContent();
                $contentDecoded = json_decode($content, false, 512, JSON_THROW_ON_ERROR);

                $token = $contentDecoded->access_token;

                // Expire cache 5 min before token expire
                $expireSeconds = intval($contentDecoded->expires_in - 300);

                $cacheItem->set($token);
                $cacheItem->expiresAfter($expireSeconds);
                $this->feedsCache->save($cacheItem);
            } catch (\Throwable $throwable) {
                throw new ColiboException($throwable->getMessage(), (int) $throwable->getCode(), $throwable);
            }
        }

        return $token;
    }
}
