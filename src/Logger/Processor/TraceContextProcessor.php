<?php

declare(strict_types=1);

namespace App\Logger\Processor;

use App\Logger\LogField;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Parses a W3C `traceparent` header when an upstream sends one, so log records
 * can be correlated with a distributed trace if/when tracing is introduced.
 * Nothing emits `traceparent` today; this is a no-op until something upstream
 * does. The fields are simply absent when no (valid) header is present — never
 * set to empty or garbage values.
 */
final readonly class TraceContextProcessor implements ProcessorInterface
{
    public function __construct(
        private RequestStack $requestStack,
    ) {}

    public function __invoke(LogRecord $record): LogRecord
    {
        $request = $this->requestStack->getMainRequest();
        $traceparent = $request?->headers->get('traceparent');

        // W3C traceparent: 00-<32 hex trace-id>-<16 hex span-id>-<2 hex flags>
        if (null !== $traceparent
            && 1 === preg_match('/^00-([0-9a-f]{32})-([0-9a-f]{16})-[0-9a-f]{2}$/', $traceparent, $m)
        ) {
            $record->extra[LogField::TRACE_ID] = $m[1];
            $record->extra[LogField::SPAN_ID] = $m[2];
        }

        return $record;
    }
}
