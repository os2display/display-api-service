<?php

declare(strict_types=1);

namespace App\HttpClient;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;

class LoggingHttpClient implements HttpClientInterface
{
    public function __construct(
        private HttpClientInterface $client,
        private readonly LoggerInterface $logger,
    ) {}

    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        $startTime = microtime(true);

        $response = $this->client->request($method, $url, $options);

        try {
            $statusCode = $response->getStatusCode();
        } catch (\Throwable $throwable) {
            // OTel semantic conventions for an HTTP client call; the exception
            // goes under the `exception` key so ExceptionContextProcessor
            // serialises it (see docs/logging.md).
            $this->logger->error('{http.request.method} {url.full} failed', [
                'http.request.method' => $method,
                'url.full' => $url,
                'http.client.request.duration' => $this->durationSeconds($startTime),
                'exception' => $throwable,
            ]);

            throw $throwable;
        }

        // Natural severity: a completed request is info; failures log at error
        // (above). Visibility is controlled by the outbound_http handler
        // threshold (LOG_LEVEL_OUTBOUND_HTTP), consistent with every channel.
        $this->logger->info('{http.request.method} {url.full} {http.response.status_code} ({http.client.request.duration}s)', [
            'http.request.method' => $method,
            'url.full' => $url,
            'http.response.status_code' => $statusCode,
            'http.client.request.duration' => $this->durationSeconds($startTime),
        ]);

        return $response;
    }

    /**
     * Elapsed time since $startTime in seconds (OTel
     * `http.client.request.duration` unit), rounded to 0.1 ms.
     */
    private function durationSeconds(float $startTime): float
    {
        return round(microtime(true) - $startTime, 4);
    }

    public function stream(ResponseInterface|iterable $responses, ?float $timeout = null): ResponseStreamInterface
    {
        return $this->client->stream($responses, $timeout);
    }

    public function withOptions(array $options): static
    {
        $clone = clone $this;
        $clone->client = $this->client->withOptions($options);

        return $clone;
    }
}
