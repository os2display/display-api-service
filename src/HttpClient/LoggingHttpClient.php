<?php

declare(strict_types=1);

namespace App\HttpClient;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;

class LoggingHttpClient implements HttpClientInterface
{
    private const REDACTED = '[redacted]';

    public function __construct(
        private HttpClientInterface $client,
        private readonly LoggerInterface $outboundHttpLogger,
    ) {}

    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        $startTime = microtime(true);

        $response = $this->client->request($method, $url, $options);

        // The actual request uses the full $url; only the logged value is
        // sanitised (see sanitizeUrl).
        $loggedUrl = $this->sanitizeUrl($url);

        try {
            $statusCode = $response->getStatusCode();
        } catch (\Throwable $throwable) {
            // OTel semantic conventions for an HTTP client call; the exception
            // goes under the `exception` key so ExceptionContextProcessor
            // serialises it (see docs/logging.md).
            $this->outboundHttpLogger->error('{http.request.method} {url.full} failed', [
                'http.request.method' => $method,
                'url.full' => $loggedUrl,
                'http.client.request.duration' => $this->durationSeconds($startTime),
                'exception' => $throwable,
            ]);

            throw $throwable;
        }

        // Natural severity: a completed request is info; failures log at error
        // (above). Visibility is controlled by the outbound_http handler
        // threshold (LOG_LEVEL_OUTBOUND_HTTP), consistent with every channel.
        $this->outboundHttpLogger->info('{http.request.method} {url.full} {http.response.status_code} ({http.client.request.duration}s)', [
            'http.request.method' => $method,
            'url.full' => $loggedUrl,
            'http.response.status_code' => $statusCode,
            'http.client.request.duration' => $this->durationSeconds($startTime),
        ]);

        return $response;
    }

    /**
     * Sanitises a URL for logging: the query string is replaced wholesale with
     * a redaction marker (`?[redacted]`), and the fragment and any userinfo
     * (`user:pass@`) are dropped, keeping scheme + host + port + path.
     *
     * The {@see \App\Logger\Processor\SensitiveDataProcessor} backstop only
     * redacts by context *key* name; a credential carried in a URL *value*
     * (`?api_key=…`, `?token=…`, or `https://user:pass@host/…`) would otherwise
     * pass through unredacted. URL values must therefore be sanitised here, at
     * the source. The query is where secrets live, so its contents are redacted
     * rather than dropped — the `?[redacted]` marker preserves the signal that
     * a query was present without leaking any of its values.
     */
    private function sanitizeUrl(string $url): string
    {
        $parts = parse_url($url);
        if (false === $parts) {
            // Unparseable: redact from the first query delimiter onward rather
            // than risk emitting a credential-bearing tail.
            $base = explode('#', explode('?', $url, 2)[0], 2)[0];

            return str_contains($url, '?') ? $base.'?'.self::REDACTED : $base;
        }

        $scheme = isset($parts['scheme']) ? $parts['scheme'].'://' : '';
        $host = $parts['host'] ?? '';
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';
        $path = $parts['path'] ?? '';
        // Show that a query existed, but never its contents.
        $query = isset($parts['query']) ? '?'.self::REDACTED : '';

        // No host (e.g. a relative URL resolved against the client's base_uri):
        // keep just the path, still with the query redacted.
        if ('' === $host) {
            return $path.$query;
        }

        return $scheme.$host.$port.$path.$query;
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
