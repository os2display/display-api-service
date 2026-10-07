<?php

declare(strict_types=1);

namespace App\Tests\Logger\Processor;

use App\Logger\Processor\ExceptionContextProcessor;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\TestCase;

class ExceptionContextProcessorTest extends TestCase
{
    public function testThrowableIsNormalisedToStructuredArray(): void
    {
        $record = $this->process(['exception' => new \RuntimeException('boom', 42)]);

        $exception = $record->context['exception'];
        $this->assertIsArray($exception);
        $this->assertSame(\RuntimeException::class, $exception['type']);
        $this->assertSame('boom', $exception['message']);
        $this->assertSame(42, $exception['code']);
        $this->assertArrayHasKey('file', $exception);
        $this->assertArrayHasKey('line', $exception);
        $this->assertIsString(json_encode($exception, JSON_THROW_ON_ERROR));
    }

    public function testPreviousChainIsIncludedAndDepthBounded(): void
    {
        // Build a chain deeper than MAX_DEPTH (5).
        $e = new \Exception('e0');
        for ($i = 1; $i <= 7; ++$i) {
            $e = new \Exception('e'.$i, 0, $e);
        }

        $record = $this->process(['exception' => $e]);

        $depth = 1;
        $node = $record->context['exception'];
        while (isset($node['previous'])) {
            ++$depth;
            $node = $node['previous'];
        }

        $this->assertSame(5, $depth, 'Previous chain must be bounded to MAX_DEPTH');
    }

    public function testNonExceptionContextIsUntouched(): void
    {
        $record = $this->process(['error' => 'just a string', 'count' => 3]);

        $this->assertSame('just a string', $record->context['error']);
        $this->assertSame(3, $record->context['count']);
    }

    /**
     * @param array<array-key, mixed> $context
     */
    private function process(array $context): LogRecord
    {
        $processor = new ExceptionContextProcessor();

        return $processor(new LogRecord(
            new \DateTimeImmutable('2026-06-02T00:00:00+00:00'),
            'interactive',
            Level::Error,
            'test',
            $context,
        ));
    }
}
