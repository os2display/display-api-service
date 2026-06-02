<?php

declare(strict_types=1);

namespace App\Logger\Processor;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Parses a W3C `traceparent` header, when present, so log records can correlate
 * with the OTLP traces the React kiosk emits. The fields are simply absent when
 * no (valid) header is present — never set to empty or garbage values.
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
            $record->extra['trace_id'] = $m[1];
            $record->extra['span_id'] = $m[2];
        }

        return $record;
    }
}
