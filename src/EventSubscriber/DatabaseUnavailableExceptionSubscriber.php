<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Doctrine\DBAL\Exception\ConnectionException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Surfaces database connectivity failures as "503 Service Unavailable".
 *
 * Without this, an unreachable database surfaces as a generic 500 — and in
 * the authentication flows it risks being mistaken for an authentication
 * failure, making clients (e.g. the screen client) discard their tokens and
 * log out. A 503 with a Retry-After header tells clients the outage is
 * temporary: retry later, do not re-authenticate.
 *
 * Logs on the `database` channel (ADR 011). Connection-establishment
 * failures themselves are logged at `critical` by ConnectionErrorMiddleware;
 * the record here adds that a request was answered 503 because of one.
 */
class DatabaseUnavailableExceptionSubscriber implements EventSubscriberInterface
{
    final public const int RETRY_AFTER_SECONDS = 10;

    public function __construct(
        private readonly LoggerInterface $databaseLogger,
    ) {}

    public static function getSubscribedEvents(): array
    {
        // Positive priority so this runs before the framework's exception
        // listeners (Symfony's ErrorListener and API Platform's exception
        // listener) render a response from the original throwable.
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 50],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $throwable = $event->getThrowable();

        // The connection failure may be wrapped by other layers, so walk the
        // exception chain. ConnectionException covers failure to connect,
        // lost connections (ConnectionLost) and refused credentials.
        for ($e = $throwable; null !== $e; $e = $e->getPrevious()) {
            if ($e instanceof ConnectionException) {
                $this->databaseLogger->error('Answering 503 Service Unavailable because the database is unavailable', [
                    'event' => 'db.unavailable',
                    'exception' => $throwable,
                ]);

                $event->setThrowable(new ServiceUnavailableHttpException(
                    self::RETRY_AFTER_SECONDS,
                    'Service temporarily unavailable. Please try again later.',
                    $throwable,
                ));

                return;
            }
        }
    }
}
