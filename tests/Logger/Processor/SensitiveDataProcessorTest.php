<?php

declare(strict_types=1);

namespace App\Tests\Logger\Processor;

use App\Logger\Processor\SensitiveDataProcessor;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\TestCase;

class SensitiveDataProcessorTest extends TestCase
{
    public function testIpv4IsTruncatedToLastOctet(): void
    {
        $record = $this->process(extra: ['client.address' => '203.0.113.42']);

        $this->assertSame('203.0.113.0', $record->extra['client.address']);
    }

    public function testIpv6IsTruncatedToSlash48(): void
    {
        $record = $this->process(extra: ['client.address' => '2001:db8:abcd:1234:5678:9abc:def0:1234']);

        $this->assertSame('2001:db8:abcd:0:0:0:0:0', $record->extra['client.address']);
    }

    public function testUnrecognisedAddressIsRedacted(): void
    {
        $record = $this->process(extra: ['client.address' => 'not-an-ip']);

        $this->assertSame('[redacted]', $record->extra['client.address']);
    }

    public function testSecretKeysAreRedactedAtAnyDepth(): void
    {
        $record = $this->process(context: [
            'password' => 'hunter2',
            'authorization' => 'Bearer abc',
            'api_key' => 'k-123',
            'nested' => ['refresh_token' => 'r-456', 'safe' => 'keep'],
            'access_token' => 'a-789',
        ]);

        $this->assertSame('[redacted]', $record->context['password']);
        $this->assertSame('[redacted]', $record->context['authorization']);
        $this->assertSame('[redacted]', $record->context['api_key']);
        $this->assertSame('[redacted]', $record->context['nested']['refresh_token']);
        $this->assertSame('[redacted]', $record->context['access_token']);
        $this->assertSame('keep', $record->context['nested']['safe']);
    }

    public function testTenantKeyIsNotRedacted(): void
    {
        $record = $this->process(extra: ['tenant.key' => 'Example1']);

        $this->assertSame('Example1', $record->extra['tenant.key']);
    }

    /**
     * @param array<array-key, mixed> $context
     * @param array<array-key, mixed> $extra
     */
    private function process(array $context = [], array $extra = []): LogRecord
    {
        $processor = new SensitiveDataProcessor();

        return $processor(new LogRecord(
            new \DateTimeImmutable('2026-06-02T00:00:00+00:00'),
            'outbound_http',
            Level::Info,
            'test',
            $context,
            $extra,
        ));
    }
}
