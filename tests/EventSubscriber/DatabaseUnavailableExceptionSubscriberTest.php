<?php

declare(strict_types=1);

namespace App\Tests\EventSubscriber;

use App\EventSubscriber\DatabaseUnavailableExceptionSubscriber;
use Doctrine\DBAL\Driver\PDO\Exception as PdoDriverException;
use Doctrine\DBAL\Exception\ConnectionException;
use Monolog\Handler\TestHandler;
use Monolog\Level;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class DatabaseUnavailableExceptionSubscriberTest extends TestCase
{
    private TestHandler $handler;
    private DatabaseUnavailableExceptionSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->handler = new TestHandler();
        $this->subscriber = new DatabaseUnavailableExceptionSubscriber(new Logger('database', [$this->handler]));
    }

    public function testWrappedConnectionExceptionBecomes503AndLogsError(): void
    {
        $connectionException = new ConnectionException(
            PdoDriverException::new(new \PDOException('SQLSTATE[HY000] [2002] Connection refused')),
            null,
        );
        // Wrapped by another layer, as it may be when it escapes a service.
        $exception = new \RuntimeException('wrapped', 0, $connectionException);
        $event = $this->createExceptionEvent($exception);

        $this->subscriber->onKernelException($event);

        $throwable = $event->getThrowable();
        $this->assertInstanceOf(ServiceUnavailableHttpException::class, $throwable);
        $this->assertSame($exception, $throwable->getPrevious());
        $this->assertTrue($throwable->getHeaders()['Retry-After'] > 0);

        $this->assertTrue($this->handler->hasRecordThatMatches('/database is unavailable/', Level::Error));
        $record = $this->handler->getRecords()[0];
        $this->assertSame('db.unavailable', $record->context['event']);
        $this->assertSame($exception, $record->context['exception']);
    }

    public function testUnrelatedExceptionIsLeftAloneAndLogsNothing(): void
    {
        $exception = new \RuntimeException('something else');
        $event = $this->createExceptionEvent($exception);

        $this->subscriber->onKernelException($event);

        $this->assertSame($exception, $event->getThrowable());
        $this->assertSame([], $this->handler->getRecords());
    }

    private function createExceptionEvent(\Throwable $throwable): ExceptionEvent
    {
        return new ExceptionEvent(
            $this->createMock(HttpKernelInterface::class),
            Request::create('/v2/layouts', Request::METHOD_GET),
            HttpKernelInterface::MAIN_REQUEST,
            $throwable,
        );
    }
}
