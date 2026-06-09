<?php

declare(strict_types=1);

namespace App\Tests\Doctrine\Middleware;

use App\Doctrine\Middleware\ConnectionErrorDriver;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Connection as DriverConnection;
use Doctrine\DBAL\Driver\Exception as DriverException;
use Monolog\Handler\TestHandler;
use Monolog\Level;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ConnectionErrorDriverTest extends TestCase
{
    public function testConnectionPressureCodeIsLoggedAsCriticalWithoutSecrets(): void
    {
        $handler = new TestHandler();
        $driver = $this->driverThrowing(2003, 'HY000'); // CR_CONN_HOST_ERROR

        $this->expectException(DriverException::class);

        try {
            (new ConnectionErrorDriver($driver, new Logger('database', [$handler])))
                ->connect(['host' => 'db.internal', 'password' => 's3cr3t', 'user' => 'app']);
        } finally {
            $records = $handler->getRecords();
            $this->assertCount(1, $records);
            $record = $records[0];

            $this->assertSame(Level::Critical, $record->level);
            $this->assertSame('database', $record->channel);
            $this->assertSame('Database connection failed', $record->message);
            $this->assertSame('db.connection_error', $record->context['event']);
            $this->assertSame('2003', $record->context['db.response.status_code']);
            $this->assertSame('connection_refused', $record->context['error.type']);
            $this->assertSame('HY000', $record->context['db.sqlstate']);
            $this->assertSame('db.internal', $record->context['db.host']);

            // The middleware logs the host only — never the password or DSN.
            $serialised = json_encode($record->toArray(), JSON_THROW_ON_ERROR | JSON_PARTIAL_OUTPUT_ON_ERROR);
            $this->assertStringNotContainsString('s3cr3t', $serialised);
            $this->assertArrayNotHasKey('password', $record->context);
        }
    }

    public function testCredentialErrorIsLoggedAsError(): void
    {
        $handler = new TestHandler();
        $driver = $this->driverThrowing(1045, '28000'); // ER_ACCESS_DENIED_ERROR

        try {
            (new ConnectionErrorDriver($driver, new Logger('database', [$handler])))
                ->connect(['host' => 'db.internal']);
            $this->fail('Expected the driver exception to be rethrown.');
        } catch (DriverException) {
            $record = $handler->getRecords()[0];
            $this->assertSame(Level::Error, $record->level);
            $this->assertSame('1045', $record->context['db.response.status_code']);
            $this->assertSame('access_denied', $record->context['error.type']);
        }
    }

    public function testSuccessfulConnectionIsNotLoggedAndPassesThrough(): void
    {
        $handler = new TestHandler();
        $connection = $this->createMock(DriverConnection::class);
        $driver = $this->createMock(Driver::class);
        $driver->method('connect')->willReturn($connection);

        $result = (new ConnectionErrorDriver($driver, new Logger('database', [$handler])))
            ->connect(['host' => 'db.internal']);

        // The wrapped connection passes through untouched and nothing is logged.
        $this->assertSame($connection, $result);
        $this->assertSame([], $handler->getRecords());
    }

    public function testOriginalExceptionInstanceIsRethrown(): void
    {
        $handler = new TestHandler();
        $exception = $this->driverException(2003, 'HY000');
        $driver = $this->driverThrowingException($exception);

        try {
            (new ConnectionErrorDriver($driver, new Logger('database', [$handler])))
                ->connect(['host' => 'db.internal']);
            $this->fail('Expected the driver exception to be rethrown.');
        } catch (DriverException $caught) {
            // The exact original instance must propagate — not a wrapper that
            // would drop the driver code / previous cause DBAL relies on.
            $this->assertSame($exception, $caught);
        }
    }

    public function testThrowingLoggerDoesNotMaskTheConnectionError(): void
    {
        $exception = $this->driverException(2003, 'HY000');
        $driver = $this->driverThrowingException($exception);

        // A logger whose write fails — e.g. an unwritable LOG_PATH during the
        // very outage this middleware reports on.
        $logger = $this->createMock(LoggerInterface::class);
        $logger->method('log')->willThrowException(new \RuntimeException('log sink unavailable'));

        try {
            (new ConnectionErrorDriver($driver, $logger))->connect(['host' => 'db.internal']);
            $this->fail('Expected the driver exception to be rethrown.');
        } catch (\Throwable $caught) {
            // The real DB connection error wins; the logging failure is swallowed.
            $this->assertSame($exception, $caught);
        }
    }

    private function driverThrowing(int $code, string $sqlState): Driver
    {
        return $this->driverThrowingException($this->driverException($code, $sqlState));
    }

    private function driverException(int $code, string $sqlState): DriverException
    {
        return new class($code, $sqlState) extends \RuntimeException implements DriverException {
            public function __construct(
                int $code,
                private readonly string $sqlState,
            ) {
                parent::__construct('driver failure', $code);
            }

            public function getSQLState(): ?string
            {
                return $this->sqlState;
            }
        };
    }

    private function driverThrowingException(DriverException $exception): Driver
    {
        $driver = $this->createMock(Driver::class);
        $driver->method('connect')->willThrowException($exception);

        return $driver;
    }
}
