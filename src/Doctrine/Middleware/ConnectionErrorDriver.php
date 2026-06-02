<?php

declare(strict_types=1);

namespace App\Doctrine\Middleware;

use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Connection as DriverConnection;
use Doctrine\DBAL\Driver\Exception as DriverException;
use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;
use Psr\Log\LoggerInterface;

/**
 * Logs MariaDB/MySQL connection-establishment failures on the `database`
 * channel, reading the raw driver error code (a middleware sits below DBAL's
 * exception conversion, which does not classify 1040/1203/2003/2013 as
 * ConnectionException). Fires for every SAPI and regardless of whether
 * application code later swallows the exception. The original exception is
 * rethrown unchanged.
 */
final class ConnectionErrorDriver extends AbstractDriverMiddleware
{
    /**
     * MySQL/MariaDB error numbers that indicate connection PRESSURE or
     * unreachability (as opposed to e.g. a bad credential typo). Logged at
     * `critical`; other connect failures at `error`.
     *
     * @var list<int>
     */
    private const CONNECTION_PRESSURE = [
        1040, // ER_CON_COUNT_ERROR  — too many connections
        1203, // ER_TOO_MANY_USER_CONNECTIONS
        1226, // ER_USER_LIMIT_REACHED
        2002, // CR_CONNECTION_ERROR — can't connect via socket
        2003, // CR_CONN_HOST_ERROR  — can't connect via TCP (refused / host down)
        2005, // CR_UNKNOWN_HOST
    ];

    public function __construct(
        Driver $driver,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct($driver);
    }

    public function connect(array $params): DriverConnection
    {
        try {
            return parent::connect($params);
        } catch (DriverException $e) {
            try {
                $code = $e->getCode();
                $level = in_array($code, self::CONNECTION_PRESSURE, true) ? 'critical' : 'error';

                $this->logger->log($level, 'Database connection failed', [
                    'event' => 'db.connection_error',
                    'db.error_code' => $code,
                    'db.sqlstate' => $e->getSQLState(),
                    'db.host' => $params['host'] ?? null,   // host only — never password/DSN
                    'exception' => $e,
                ]);
            } catch (\Throwable) { // @phpstan-ignore logging.silentCatch (a failing log write — e.g. unwritable LOG_PATH during the same outage — must never mask the real connection error rethrown below)
                // Swallow logging failures so the original connection exception
                // always propagates unchanged (the class's documented contract).
            }

            throw $e;
        }
    }
}
