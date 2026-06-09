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

    /**
     * MySQL/MariaDB error numbers mapped to a stable, low-cardinality OTel
     * `error.type` token (the human-readable categorisation; the verbose driver
     * message stays in `exception.message`). Codes not listed fall back to the
     * stringified code per OTel guidance, so `error.type` is always present.
     *
     * @var array<int, string>
     */
    private const ERROR_TYPE = [
        1040 => 'too_many_connections',
        1203 => 'too_many_user_connections',
        1226 => 'user_resource_limit',
        2002 => 'connection_refused',
        2003 => 'connection_refused',
        2005 => 'unknown_host',
        1045 => 'access_denied',
        1044 => 'access_denied',
        1049 => 'unknown_database',
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

                $context = [
                    'event' => 'db.connection_error',
                    // OTel db semconv: the DB-specific status/error code, as a string.
                    'db.response.status_code' => (string) $code,
                    // OTel: low-cardinality categorisation of the failure.
                    'error.type' => self::ERROR_TYPE[$code] ?? (string) $code,
                    'db.sqlstate' => $e->getSQLState(),
                    // OTel server.* — host only, never the password or full DSN.
                    'server.address' => $params['host'] ?? null,
                    'exception' => $e,
                ];
                // OTel pairs server.address with server.port; omit when absent.
                if (isset($params['port'])) {
                    $context['server.port'] = $params['port'];
                }

                $this->logger->log($level, 'Database connection failed', $context);
            } catch (\Throwable) { // @phpstan-ignore logging.silentCatch (a failing log write — e.g. unwritable LOG_PATH during the same outage — must never mask the real connection error rethrown below)
                // Swallow logging failures so the original connection exception
                // always propagates unchanged (the class's documented contract).
            }

            throw $e;
        }
    }
}
