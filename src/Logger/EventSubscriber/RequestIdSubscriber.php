<?php

declare(strict_types=1);

namespace App\Logger\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Uid\Uuid;

/**
 * Adopts the inbound `X-Request-Id` (nginx-generated) or mints one, stores it on
 * the request, and echoes it on the response. No format validation — the upstream
 * proxy decides the format.
 */
final class RequestIdSubscriber implements EventSubscriberInterface
{
    private const ATTR = '_request_id';
    private const HEADER = 'X-Request-Id';

    public static function getSubscribedEvents(): array
    {
        return [
            // High priority so the id exists before anything else logs.
            KernelEvents::REQUEST => ['onRequest', 4096],
            KernelEvents::RESPONSE => ['onResponse', -4096],
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }
        $request = $event->getRequest();
        $id = $request->headers->get(self::HEADER) ?: (string) Uuid::v4();
        $request->attributes->set(self::ATTR, $id);
    }

    public function onResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }
        $id = $event->getRequest()->attributes->get(self::ATTR);
        if (null !== $id && !$event->getResponse()->headers->has(self::HEADER)) {
            $event->getResponse()->headers->set(self::HEADER, $id);
        }
    }
}
