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
        private readonly string $logLevel = 'info',
    ) {}

    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        $startTime = microtime(true);

        $response = $this->client->request($method, $url, $options);

        try {
            $statusCode = $response->getStatusCode();
        } catch (\Throwable $throwable) {
            $duration = round((microtime(true) - $startTime) * 1000);

            $this->logger->error('{method} {url} failed after {duration}ms: {error}', [
                'method' => $method,
                'url' => $url,
                'duration' => $duration,
                'error' => $throwable->getMessage(),
            ]);

            throw $throwable;
        }

        $duration = round((microtime(true) - $startTime) * 1000);

        $this->logger->log($this->logLevel, '{method} {url} {status_code} {duration}ms', [
            'method' => $method,
            'url' => $url,
            'status_code' => $statusCode,
            'duration' => $duration,
        ]);

        return $response;
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
