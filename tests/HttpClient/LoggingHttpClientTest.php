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
    public function testRequestLogsAtConfiguredLevel(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);

        $inner = $this->createMock(HttpClientInterface::class);
        $inner->method('request')->willReturn($response);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('log')
            ->with(
                'info',
                '{method} {url} {status_code} {duration}ms',
                $this->callback(fn (array $context) => 'GET' === $context['method']
                    && 'https://example.com/api' === $context['url']
                    && 200 === $context['status_code']
                    && is_float($context['duration']))
            );

        $client = new LoggingHttpClient($inner, $logger, 'info');
        $result = $client->request('GET', 'https://example.com/api');

        $this->assertSame($response, $result);
    }

    public function testRequestLogsAtErrorLevelByDefault(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);

        $inner = $this->createMock(HttpClientInterface::class);
        $inner->method('request')->willReturn($response);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('log')
            ->with(
                'error',
                $this->anything(),
                $this->anything()
            );

        $client = new LoggingHttpClient($inner, $logger);
        $client->request('GET', 'https://example.com/api');
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
                '{method} {url} failed after {duration}ms: {error}',
                $this->callback(fn (array $context) => 'POST' === $context['method']
                    && 'https://example.com/fail' === $context['url']
                    && 'Connection refused' === $context['error']
                    && is_float($context['duration']))
            );
        // log() should NOT be called when the error path is taken
        $logger->expects($this->never())->method('log');

        $client = new LoggingHttpClient($inner, $logger, 'info');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Connection refused');

        $client->request('POST', 'https://example.com/fail');
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

        $client = new LoggingHttpClient($inner, $logger, 'info');
        $newClient = $client->withOptions($options);

        $this->assertNotSame($client, $newClient);
        $this->assertInstanceOf(LoggingHttpClient::class, $newClient);

        // Verify the new client uses the new inner client
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $newInner->expects($this->once())->method('request')->willReturn($response);
        $logger->expects($this->once())->method('log');

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
            ->method('log')
            ->with(
                'info',
                '{method} {url} {status_code} {duration}ms',
                $this->callback(fn (array $context) => 500 === $context['status_code'])
            );

        $client = new LoggingHttpClient($inner, $logger, 'info');
        $client->request('GET', 'https://example.com/error');
    }
}
