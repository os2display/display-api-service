<?php

declare(strict_types=1);

namespace App\Logger\Processor;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

/**
 * Replaces a `\Throwable` under the `exception` context key with a structured
 * array (type, message, code, file, line and a bounded `previous` chain) so a
 * raw multi-line stack-trace string is never emitted at info level in
 * production. Non-exception context is left untouched.
 */
final class ExceptionContextProcessor implements ProcessorInterface
{
    private const MAX_DEPTH = 5;

    public function __invoke(LogRecord $record): LogRecord
    {
        $context = $record->context;
        if (isset($context['exception']) && $context['exception'] instanceof \Throwable) {
            $context['exception'] = $this->normalize($context['exception'], self::MAX_DEPTH);

            return $record->with(context: $context);
        }

        return $record;
    }

    /** @return array<string, mixed> */
    private function normalize(\Throwable $e, int $depth): array
    {
        $data = [
            'type' => $e::class,
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ];

        $previous = $e->getPrevious();
        if (null !== $previous && $depth > 1) {
            $data['previous'] = $this->normalize($previous, $depth - 1);
        }

        return $data;
    }
}
