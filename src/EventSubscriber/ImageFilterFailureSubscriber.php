<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Logs failed image filter requests (thumbnails) on the `media` channel (ADR 011).
 *
 * Liip generates filtered images on the first request to its resolve routes
 * and turns a failure into an uncaught exception, which otherwise only reaches
 * the generic request log without the filter or source path. A source image
 * that cannot be found is logged at `warning`; anything else, such as the
 * driver (ImageMagick) failing or hitting a resource limit, at `error`. A
 * failed screen variant redirects to the original image.
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
        $filter = (string) $request->attributes->get('filter');
        $path = (string) $request->attributes->get('path');
        $sourceMissing = $throwable instanceof NotFoundHttpException;

        $this->mediaLogger->log($sourceMissing ? 'warning' : 'error', 'Image filter failed', [
            'event' => 'media.image_filter_failed',
            'image.filter' => $filter,
            'file.path' => $path,
            'exception' => $throwable,
        ]);

        // A failed screen variant redirects to the original image, so whatever
        // requested the variant (screen client, admin preview, /template) shows
        // the original instead of nothing: templates use the variant as a CSS
        // background, which has no error event to fall back on. The API output
        // is unchanged; assets.variants still lists the variant URL. The
        // redirect is cached for an hour, so a browser retries the failing
        // resize at most hourly.
        if (!$sourceMissing && str_starts_with($filter, 'screen_')) {
            $response = new RedirectResponse($request->getBasePath().'/'.ltrim($path, '/'));
            $response->setPublic();
            $response->setMaxAge(3600);
            $event->setResponse($response);
        }
    }
}
