<?php

declare(strict_types=1);

namespace App\Feed\SourceType\Brnd;

use App\Entity\Tenant\FeedSource;
use App\Feed\BrndFeedType;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class ApiClient
{
    private const string ASSOCIATION_TYPE = 'Company';

    /** @var array<string, HttpClientInterface> */
    private array $apiClients = [];

    public function __construct(
        private readonly CacheItemPoolInterface $feedsCache,
        private readonly LoggerInterface $logger,
    ) {}

    
    /**
     * Retrieve bookings based on the given feed source and sportCenterId.
     *
     * @param FeedSource $feedSource
     * @param string $sportCenterId
     * @param string $startDate
     * @param string $endDate
     *
     * @return array
     */
    public function getBookingInfo(FeedSource $feedSource, string $sportCenterId): array
    {
        try {
            $responseData = $this->getBookingInfoPage($feedSource, $sportCenterId)->toArray();

            $bookings = $responseData['data'];

            return $bookings;
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
     * @param string $sportCenterId
     * @param string $startDate
     * @param string $endDate
     *
     * @return ResponseInterface
     *
     * @throws BrndException
     */
    private function getBookingInfoPage(
        FeedSource $feedSource,
        string $sportCenterId,
        ?string $startDate = null,
        ?string $endDate = null
    ): ResponseInterface {
        $startDate = $startDate ?? date('Y-m-d');
        $endDate = $endDate ?? date('Y-m-d');

        try {
            $client = $this->getApiClient($feedSource);

            return $client->request('POST', '/v1.0/booking-info', [
                'body' => [
                    'sportCenterId' => $sportCenterId,
                    'startDate' => $startDate,
                    'endDate' => $endDate,
                ],
            ]);
        } catch (BrndException $exception) {
            throw $exception;
        } catch (\Throwable $throwable) {
            throw new BrndException($throwable->getMessage(), (int) $throwable->getCode(), $throwable);
        }
    }

    /**
     * Get an authenticated scoped API client for the given FeedSource.
     *
     * @param FeedSource $feedSource
     *
     * @return HttpClientInterface
     *
     * @throws BrndException
     */
    private function getApiClient(FeedSource $feedSource): HttpClientInterface
    {
        $id = BrndFeedType::getIdKey($feedSource);

        if (array_key_exists($id, $this->apiClients)) {
            return $this->apiClients[$id];
        }

        $secrets = new SecretsDTO($feedSource);
        $this->apiClients[$id] = HttpClient::createForBaseUri($secrets->apiBaseUri)->withOptions([
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer '.$this->fetchToken($feedSource),
                'Accept' => '*/*',
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
     * @throws BrndException
     */
    private function fetchToken(FeedSource $feedSource): string
    {
        $id = BrndFeedType::getIdKey($feedSource);

        /** @var CacheItemInterface $cacheItem */
        $cacheItem = $this->feedsCache->getItem('brnd_token_'.$id);

        if ($cacheItem->isHit()) {
            /** @var string $token */
            $token = $cacheItem->get();
        } else {
            try {
                $secrets = new SecretsDTO($feedSource);
                $client = HttpClient::createForBaseUri($secrets->apiBaseUri);

                $response = $client->request('POST', '/v1.0/generate-token', [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Accept' => '*/*',
                    ],
                    'body' => [
                        'associationType' => self::ASSOCIATION_TYPE,
                        'apiAuthKey' => $secrets->apiAuthKey,
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
                throw new BrndException($throwable->getMessage(), (int) $throwable->getCode(), $throwable);
            }
        }

        return $token;
    }
}
