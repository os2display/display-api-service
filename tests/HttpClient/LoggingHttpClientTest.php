<?php

declare(strict_types=1);

namespace App\Tests\HttpClient;

use App\HttpClient\LoggingHttpClient;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;

class LoggingHttpClientTest extends TestCase
{
    public function testCompletedRequestIsLoggedAtInfo(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);

        $inner = $this->createMock(HttpClientInterface::class);
        $inner->method('request')->willReturn($response);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('info')
            ->with(
                '{http.request.method} {url.full} {http.response.status_code} ({http.client.request.duration}s)',
                $this->callback(fn (array $context) => 'GET' === $context['http.request.method']
                    && 'https://example.com/api' === $context['url.full']
                    && 200 === $context['http.response.status_code']
                    && is_float($context['http.client.request.duration']))
            );

        $client = new LoggingHttpClient($inner, $logger);
        $result = $client->request('GET', 'https://example.com/api');

        $this->assertSame($response, $result);
    }

    public function testRequestLogsErrorOnTransportException(): void
    {
        $inner = $this->createMock(HttpClientInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')
            ->willThrowException(new \RuntimeException('Connection refused'));
        $inner->method('request')->willReturn($response);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with(
                '{http.request.method} {url.full} failed',
                $this->callback(fn (array $context) => 'POST' === $context['http.request.method']
                    && 'https://example.com/fail' === $context['url.full']
                    && $context['exception'] instanceof \RuntimeException
                    && 'Connection refused' === $context['exception']->getMessage()
                    && is_float($context['http.client.request.duration']))
            );
        // The success log must NOT be emitted when the error path is taken.
        $logger->expects($this->never())->method('info');

        $client = new LoggingHttpClient($inner, $logger);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Connection refused');

        $client->request('POST', 'https://example.com/fail');
    }

    /**
     * The inner client must receive the full URL (query intact) while the
     * logged `url.full` has its query redacted wholesale.
     *
     * @dataProvider urlSanitizationProvider
     */
    public function testUrlQueryIsRedactedInLogsButNotInRequest(string $requestUrl, string $expectedLoggedUrl): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);

        $inner = $this->createMock(HttpClientInterface::class);
        // The real request keeps the full, unredacted URL.
        $inner->expects($this->once())
            ->method('request')
            ->with('GET', $requestUrl)
            ->willReturn($response);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('info')
            ->with(
                $this->anything(),
                $this->callback(fn (array $context) => $expectedLoggedUrl === $context['url.full'])
            );

        $client = new LoggingHttpClient($inner, $logger);
        $client->request('GET', $requestUrl);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function urlSanitizationProvider(): iterable
    {
        yield 'query with secrets is redacted wholesale' => [
            'https://example.com/api?api_key=SECRET&token=abc123',
            'https://example.com/api?[redacted]',
        ];
        yield 'a single query param is still redacted' => [
            'https://example.com/api?token=abc123',
            'https://example.com/api?[redacted]',
        ];
        yield 'no query is left untouched' => [
            'https://example.com/api',
            'https://example.com/api',
        ];
        yield 'fragment is dropped' => [
            'https://example.com/api#section',
            'https://example.com/api',
        ];
        yield 'userinfo credentials are dropped' => [
            'https://user:pass@example.com/api?token=abc',
            'https://example.com/api?[redacted]',
        ];
        yield 'port is preserved' => [
            'https://example.com:8443/api?token=abc',
            'https://example.com:8443/api?[redacted]',
        ];
        yield 'relative url keeps path with query redacted' => [
            '/api/items?token=abc',
            '/api/items?[redacted]',
        ];
    }

    public function testErrorPathAlsoRedactsTheQuery(): void
    {
        $inner = $this->createMock(HttpClientInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')
            ->willThrowException(new \RuntimeException('Connection refused'));
        $inner->method('request')->willReturn($response);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with(
                '{http.request.method} {url.full} failed',
                $this->callback(fn (array $context) => 'https://example.com/fail?api_key=SECRET' !== $context['url.full']
                    && 'https://example.com/fail?[redacted]' === $context['url.full'])
            );

        $client = new LoggingHttpClient($inner, $logger);

        $this->expectException(\RuntimeException::class);
        $client->request('POST', 'https://example.com/fail?api_key=SECRET');
    }

    public function testStreamDelegatesToInnerClient(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(ResponseStreamInterface::class);

        $inner = $this->createMock(HttpClientInterface::class);
        $inner->expects($this->once())
            ->method('stream')
            ->with($response, 30.0)
            ->willReturn($stream);

        $logger = $this->createMock(LoggerInterface::class);

        $client = new LoggingHttpClient($inner, $logger);
        $result = $client->stream($response, 30.0);

        $this->assertSame($stream, $result);
    }

    public function testWithOptionsReturnsNewInstanceWithUpdatedInner(): void
    {
        $inner = $this->createMock(HttpClientInterface::class);
        $newInner = $this->createMock(HttpClientInterface::class);

        $options = ['base_uri' => 'https://example.com'];
        $inner->expects($this->once())
            ->method('withOptions')
            ->with($options)
            ->willReturn($newInner);

        $logger = $this->createMock(LoggerInterface::class);

        $client = new LoggingHttpClient($inner, $logger);
        $newClient = $client->withOptions($options);

        $this->assertNotSame($client, $newClient);
        $this->assertInstanceOf(LoggingHttpClient::class, $newClient);

        // Verify the new client uses the new inner client
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $newInner->expects($this->once())->method('request')->willReturn($response);
        $logger->expects($this->once())->method('info');

        $newClient->request('GET', '/test');
    }

    public function testNon2xxStatusCodeIsStillLogged(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(500);

        $inner = $this->createMock(HttpClientInterface::class);
        $inner->method('request')->willReturn($response);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('info')
            ->with(
                '{http.request.method} {url.full} {http.response.status_code} ({http.client.request.duration}s)',
                $this->callback(fn (array $context) => 500 === $context['http.response.status_code'])
            );

        $client = new LoggingHttpClient($inner, $logger);
        $client->request('GET', 'https://example.com/error');
    }
}
