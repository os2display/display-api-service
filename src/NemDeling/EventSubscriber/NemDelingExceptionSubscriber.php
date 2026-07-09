<?php

declare(strict_types=1);

namespace App\NemDeling\EventSubscriber;

use App\NemDeling\Exception\NemDelingConfigurationException;
use App\NemDeling\Exception\NemDelingSyncInProgressException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::EXCEPTION)]
final class NemDelingExceptionSubscriber
{
    public function onKernelException(ExceptionEvent $event): void
    {
        if (!str_starts_with($event->getRequest()->getPathInfo(), '/api/v1/nemdeling/')) {
            return;
        }

        $throwable = $event->getThrowable();
        if ($throwable instanceof NemDelingSyncInProgressException) {
            $event->setResponse(new Response($throwable->getMessage(), Response::HTTP_SERVICE_UNAVAILABLE));

            return;
        }

        if ($throwable instanceof NemDelingConfigurationException) {
            $event->setResponse(new Response($throwable->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR));
        }
    }
}
