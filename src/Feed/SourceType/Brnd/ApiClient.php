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
    private const string AUTH_ASSOCIATION_TYPE = 'Company';
    private const string BOOKINGS_ASSOCIATION_TYPE = 'Sportcenter';
    private const int STATUS_CANCELLED = 5;
    private const int STATUS_ALLOCATED = 4;
    private const int TOKEN_TTL = 1200;

    /** @var array<string, HttpClientInterface> */
    private array $apiClients = [];

    public function __construct(
        private readonly CacheItemPoolInterface $feedsCache,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Retrieve todays bookings from Infomonitor Booking API for a given sportCenterId.
     *
     * @param FeedSource $feedSource
     * @param string $sportCenterId
     *
     * @return array
     */
    public function getInfomonitorBookingsDetails(
        FeedSource $feedSource,
        string $sportCenterId
    ): array
    {
        try {
            $responseData = $this->getInfomonitorBookingsDetailsData($feedSource, $sportCenterId)->toArray();

            $bookings = [];
            if (isset($responseData['data']) && is_array($responseData['data'])) {
                foreach ($responseData['data'] as $item) {
                    if (isset($item['infoBookingDetails']) && is_array($item['infoBookingDetails'])) {
                        $bookings = array_merge($bookings, $item['infoBookingDetails']);
                    }
                }
            }

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
     * @param string|null $date Optional. Defaults to today if not provided (Y-m-d).
     * @param string|null $startTime Optional. Time in HH:MM format. Defaults to empty string if not provided (no time filter applied).
     * @param string|null $endTime Optional. Time in HH:MM format. Defaults to empty string if not provided (no time filter applied).
     * @param int[]|null $bookingStatusCodes Optional. Array of booking status codes to filter by.
     *
     * @return ResponseInterface
     *
     * @throws BrndException
     */
    private function getInfomonitorBookingsDetailsData(
        FeedSource $feedSource,
        string $sportCenterId,
        ?string $date = null,
        ?string $startTime = null,
        ?string $endTime = null,
        ?array $bookingStatusCodes = null
    ): ResponseInterface {
        $secrets = new SecretsDTO($feedSource);
        $defaultStatusCodes = [self::STATUS_ALLOCATED, self::STATUS_CANCELLED];
        $date = $date ?? date('Y-m-d');
        $startTime = $startTime ?? '';
        $endTime = $endTime ?? '';
        $bookingStatusCodes = implode(',', $bookingStatusCodes ?? $defaultStatusCodes);

        try {
            $client = $this->getApiClient($feedSource);

            return $client->request('POST', '/v1.0/get-infomonitor-bookings-details', [
                'json' => [
                    'companyID' => $secrets->companyId,
                    'associationID' => $sportCenterId,
                    'associationType' => self::BOOKINGS_ASSOCIATION_TYPE,
                    'date' => $date,
                    'startTime' => $startTime,
                    'endTime' => $endTime,
                    'statusID' => $bookingStatusCodes,
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
                $requestOptions = [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Accept' => '*/*',
                    ],
                    'json' => [
                        'associationType' => self::AUTH_ASSOCIATION_TYPE,
                        'apiAuthKey' => $secrets->apiAuthKey,
                    ],
                ];
                $response = $client->request('POST', '/v1.0/generate-token', $requestOptions);

                $content = $response->getContent(false); // Don't throw on non-2xx
                $contentDecoded = json_decode($content, false, 512, JSON_THROW_ON_ERROR);
                $token = $contentDecoded->data->access_token;

                // Expire cache 5 min before token expire
                $expireSeconds = intval(self::TOKEN_TTL - 300);
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
