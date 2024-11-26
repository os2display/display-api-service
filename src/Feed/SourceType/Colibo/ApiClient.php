<?php

namespace App\Feed\SourceType\Colibo;

use App\Entity\Tenant\FeedSource;
use Psr\Cache\CacheItemInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
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
        private readonly CacheInterface $feedsCache,
        private readonly LoggerInterface $logger,
    ) {}

    public function getFeedEntriesNews(FeedSource $feedSource, array $recipients, array $publishers): mixed
    {
        $results = [];

        try {
            $client = $this->getApiClient($feedSource);

            $response = $client->request('GET', '/api/feedentries/news', [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'query' => [
                    'getQuery.recipients' => $recipients,
                ],
            ]);

            $results = json_decode($response->getContent(), false, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $throwable) {
            $this->logger->error('{code}: {message}', [
                'code' => $throwable->getCode(),
                'message' => $throwable->getMessage(),
            ]);

            throw $throwable;
        }

        return $results;
    }

    public function getSearchGroups(FeedSource $feedSource, string $type = 'WorkGroup'): array
    {
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
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function getFeedEntryPublishersGroups(FeedSource $feedSource): array
    {
        $client = $this->getApiClient($feedSource);

        $response = $client->request('GET', '/api/feedentries/publishers/groups', [
            'query' => ['groupType' => 'Department'],
        ]);

        $groups = [];
        $childGroupIds = [];
        foreach ($response->toArray() as $group) {
            $groups[] = $group;

            if (isset($group['hasChildren']) && $group['hasChildren']) {
                $childGroupIds[] = $group['id'];
            }
        }

        $this->getFeedEntryPublishersGroupsChildren($feedSource, $childGroupIds, $groups);

        return $groups;
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    private function getSearchGroupsPage(FeedSource $feedSource, string $type, int $pageIndex = 0, int $pageSize = self::BATCH_SIZE): ResponseInterface
    {
        $client = $this->getApiClient($feedSource);

        return $client->request('GET', '/api/search/groups', [
            'query' => [
                'groupSearchQuery.groupTypes' => $type,
                'groupSearchQuery.pageIndex' => $pageIndex,
                'groupSearchQuery.pageSize' => $pageSize,
            ],
        ]);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    private function getFeedEntryPublishersGroupsChildren(FeedSource $feedSource, array $childGroupIds, array &$groups): void
    {
        $client = $this->getApiClient($feedSource);

        $batches = array_chunk($childGroupIds, self::BATCH_SIZE);

        foreach ($batches as $batch) {
            // @see https://symfony.com/doc/current/http_client.html#concurrent-requests
            $responses = [];
            foreach ($batch as $childGroupId) {
                $uri = sprintf('/api/feedentries/publishers/groups/%d/children', $childGroupId);
                $responses[] = $client->request('GET', $uri, []);
            }

            $childGroupIds = [];
            foreach ($responses as $response) {
                foreach ($response->toArray() as $group) {
                    $groups[] = $group;

                    if (isset($group['hasChildren']) && $group['hasChildren']) {
                        $childGroupIds[] = $group['id'];
                    }
                }
            }
        }

        if (!empty($childGroupIds)) {
            $this->getFeedEntryPublishersGroupsChildren($feedSource, $childGroupIds, $groups);
        }
    }

    private function getApiClient(FeedSource $feedSource): HttpClientInterface
    {
        $id = ColiboFeedType::getIdKey($feedSource);

        if (array_key_exists($id, $this->apiClients)) {
            return $this->apiClients[$id];
        }

        $secrets = new SecretsDTO($feedSource);
        $this->apiClients[$id] = HttpClient::createForBaseUri($secrets->apiBaseUri)->withOptions([
            'headers' => [
                'Authorization' => 'Bearer '.$this->fetchColiboToken($feedSource),
                'Accept' => 'application/json',
            ],
        ]);

        return $this->apiClients[$id];
    }

    /**
     * @throws TransportExceptionInterface
     * @throws \Throwable
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws \JsonException
     */
    private function fetchColiboToken(FeedSource $feedSource): string
    {
        $id = ColiboFeedType::getIdKey($feedSource);

        /** @var CacheItemInterface $cacheItem */
        $cacheItem = $this->feedsCache->getItem('colibo_token_'.$id);

        if (false && $cacheItem->isHit()) {
            /** @var string $token */
            $token = $cacheItem->get();
        } else {
            try {
                $secrets = new SecretsDTO($feedSource);
                $client = HttpClient::createForBaseUri($secrets->apiBaseUri);

                $response = $client->request('POST', '/auth/oauth2/connect/token', [
                    'headers' => [
                        'Content-Type' => 'application/x-www-form-urlencoded',
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
                $this->logger->error('{code}: {message}', [
                    'code' => $throwable->getCode(),
                    'message' => $throwable->getMessage(),
                ]);

                throw $throwable;
            }
        }

        return $token;
    }
}