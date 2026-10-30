<?php

declare(strict_types=1);

namespace App\Tests\Logger\Processor;

use App\Logger\Processor\TraceContextProcessor;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class TraceContextProcessorTest extends TestCase
{
    public function testValidTraceparentYieldsTraceAndSpanIds(): void
    {
        $request = Request::create('/');
        $request->headers->set('traceparent', '00-4bf92f3577b34da6a3ce929d0e0e4736-00f067aa0ba902b7-01');
        $stack = new RequestStack();
        $stack->push($request);

        $record = (new TraceContextProcessor($stack))($this->record());

        $this->assertSame('4bf92f3577b34da6a3ce929d0e0e4736', $record->extra['trace_id']);
        $this->assertSame('00f067aa0ba902b7', $record->extra['span_id']);
    }

    public function testInvalidTraceparentLeavesFieldsUnset(): void
    {
        $request = Request::create('/');
        $request->headers->set('traceparent', 'garbage');
        $stack = new RequestStack();
        $stack->push($request);

        $record = (new TraceContextProcessor($stack))($this->record());

        $this->assertArrayNotHasKey('trace_id', $record->extra);
        $this->assertArrayNotHasKey('span_id', $record->extra);
    }

    public function testNoRequestLeavesFieldsUnset(): void
    {
        $record = (new TraceContextProcessor(new RequestStack()))($this->record());

        $this->assertArrayNotHasKey('trace_id', $record->extra);
        $this->assertArrayNotHasKey('span_id', $record->extra);
    }

    private function record(): LogRecord
    {
        return new LogRecord(new \DateTimeImmutable('2026-06-02T00:00:00+00:00'), 'outbound_http', Level::Info, 'test');
    }
}
