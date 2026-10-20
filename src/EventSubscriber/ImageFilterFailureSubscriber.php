<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Logs failed image filter requests on the `media` channel (ADR 011).
 *
 * Liip generates filtered images on the first request to its resolve routes
 * and turns a failure into an uncaught exception, which otherwise only reaches
 * the generic request log without the filter or source path. Matching on the
 * route covers every filter set and image format. A source image
 * that cannot be found is logged at `warning`; anything else, such as the
 * driver (ImageMagick) failing or hitting a resource limit, at `error`.
 */
class ImageFilterFailureSubscriber implements EventSubscriberInterface
{
    private const array ROUTES = ['liip_imagine_filter', 'liip_imagine_filter_runtime'];

    public function __construct(
        private readonly LoggerInterface $mediaLogger,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();

        if (!\in_array($request->attributes->get('_route'), self::ROUTES, true)) {
            return;
        }

        $throwable = $event->getThrowable();

        $this->mediaLogger->log($throwable instanceof NotFoundHttpException ? 'warning' : 'error', 'Image filter failed', [
            'event' => 'media.image_filter_failed',
            'image.filter' => $request->attributes->get('filter'),
            'file.path' => $request->attributes->get('path'),
            'exception' => $throwable,
        ]);
    }
}
