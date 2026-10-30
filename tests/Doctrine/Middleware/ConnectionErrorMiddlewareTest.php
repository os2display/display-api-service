<?php

declare(strict_types=1);

namespace App\Tests\Doctrine\Middleware;

use App\Doctrine\Middleware\ConnectionErrorDriver;
use App\Doctrine\Middleware\ConnectionErrorMiddleware;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Exception as DriverException;
use Monolog\Handler\TestHandler;
use Monolog\Level;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

class ConnectionErrorMiddlewareTest extends TestCase
{
    public function testWrapDecoratesTheDriverWithConnectionErrorDriver(): void
    {
        $middleware = new ConnectionErrorMiddleware(new Logger('database', [new TestHandler()]));

        $wrapped = $middleware->wrap($this->createMock(Driver::class));

        $this->assertInstanceOf(ConnectionErrorDriver::class, $wrapped);
    }

    public function testWrappedDriverLogsConnectionFailuresOnTheInjectedLogger(): void
    {
        $handler = new TestHandler();
        $middleware = new ConnectionErrorMiddleware(new Logger('database', [$handler]));

        $driver = $this->createMock(Driver::class);
        $driver->method('connect')->willThrowException(
            new class extends \RuntimeException implements DriverException {
                public function getSQLState(): ?string
                {
                    return 'HY000';
                }
            }
        );

        try {
            $middleware->wrap($driver)->connect(['host' => 'db.internal']);
            $this->fail('Expected the driver exception to propagate.');
        } catch (DriverException) {
            // wrap() must thread the injected logger into the driver it creates.
            $this->assertTrue($handler->hasRecords(Level::Error));
        }
    }
}
