<?php

declare(strict_types=1);

namespace App\Doctrine\Middleware;

use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Middleware;
use Psr\Log\LoggerInterface;

/**
 * Wraps the DBAL driver so connection-establishment failures are logged on the
 * `database` channel before they propagate (ADR 011). Logging only — there is
 * no reconnect/retry.
 */
final readonly class ConnectionErrorMiddleware implements Middleware
{
    public function __construct(
        private LoggerInterface $databaseLogger,
    ) {}

    public function wrap(Driver $driver): Driver
    {
        return new ConnectionErrorDriver($driver, $this->databaseLogger);
    }
}
