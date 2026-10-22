<?php

declare(strict_types=1);

namespace App\Tests\EventSubscriber;

use App\EventSubscriber\ImageFilterFailureSubscriber;
use Monolog\Handler\TestHandler;
use Monolog\Level;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ImageFilterFailureSubscriberTest extends TestCase
{
    private TestHandler $handler;
    private ImageFilterFailureSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->handler = new TestHandler();
        $this->subscriber = new ImageFilterFailureSubscriber(new Logger('media', [$this->handler]));
    }

    public function testFailedImageGenerationIsLoggedAsError(): void
    {
        // What Liip throws when the driver cannot process the source image,
        // e.g. ImageMagick hitting a resource limit.
        $exception = new \RuntimeException('Unable to create image for path "media/abc/large.jpg" and filter "thumbnail".');

        $this->subscriber->onKernelException($this->createExceptionEvent('liip_imagine_filter', $exception));

        $this->assertTrue($this->handler->hasRecordThatMatches('/^Image filter failed$/', Level::Error));
        $record = $this->handler->getRecords()[0];
        $this->assertSame('media.image_filter_failed', $record->context['event']);
        $this->assertSame('thumbnail', $record->context['image.filter']);
        $this->assertSame('media/abc/large.jpg', $record->context['file.path']);
        $this->assertSame($exception, $record->context['exception']);
    }

    public function testMissingSourceImageIsLoggedAsWarning(): void
    {
        $exception = new NotFoundHttpException('Source image for path "media/abc/gone.jpg" could not be found');

        $this->subscriber->onKernelException($this->createExceptionEvent('liip_imagine_filter', $exception, 'media/abc/gone.jpg'));

        $this->assertTrue($this->handler->hasRecordThatMatches('/^Image filter failed$/', Level::Warning));
    }

    public function testFailedScreenVariantFallsBackToTheOriginal(): void
    {
        // Screens show variants as CSS backgrounds, which have no error event:
        // a failed variant would leave the slide blank.
        $event = $this->createExceptionEvent('liip_imagine_filter', new \RuntimeException('ImageMagick failed'), 'media/abc/large.jpg', 'screen_1920');

        $this->subscriber->onKernelException($event);

        $response = $event->getResponse();
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('http://localhost/media/abc/large.jpg', $response->getTargetUrl());
        $this->assertTrue($this->handler->hasRecordThatMatches('/^Image filter failed$/', Level::Error));
    }

    public function testScreenVariantFallbackIsCachedForAnHour(): void
    {
        // A failing variant is retried on every request, which is a full,
        // memory-heavy resize. Browsers cache the redirect, so each screen
        // retries at most hourly.
        $event = $this->createExceptionEvent('liip_imagine_filter', new \RuntimeException('ImageMagick failed'), 'media/abc/large.jpg', 'screen_1920');

        $this->subscriber->onKernelException($event);

        $response = $event->getResponse();
        $this->assertNotNull($response);
        $this->assertTrue($response->headers->hasCacheControlDirective('public'));
        $this->assertSame('3600', $response->headers->getCacheControlDirective('max-age'));
    }

    public function testFailedThumbnailHasNoFallback(): void
    {
        $event = $this->createExceptionEvent('liip_imagine_filter', new \RuntimeException('ImageMagick failed'));

        $this->subscriber->onKernelException($event);

        $this->assertNull($event->getResponse());
    }

    public function testMissingSourceOfScreenVariantHasNoFallback(): void
    {
        $event = $this->createExceptionEvent('liip_imagine_filter', new NotFoundHttpException('gone'), 'media/abc/gone.jpg', 'screen_1920');

        $this->subscriber->onKernelException($event);

        $this->assertNull($event->getResponse());
    }

    /**
     * @dataProvider unsafePaths
     */
    public function testScreenVariantFallbackStaysOnTheHost(string $path): void
    {
        $event = $this->createExceptionEvent('liip_imagine_filter', new \RuntimeException('ImageMagick failed'), $path, 'screen_1920');

        $this->subscriber->onKernelException($event);

        $response = $event->getResponse();
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertStringStartsWith('http://localhost/', $response->getTargetUrl());
    }

    /**
     * Paths a browser would read as a protocol-relative URL in a relative redirect.
     *
     * @return iterable<string, array{string}>
     */
    public static function unsafePaths(): iterable
    {
        yield 'backslash' => ['\example.com/x.jpg'];
        yield 'tab' => ["\t/example.com/x.jpg"];
        yield 'newline' => ["\n/example.com/x.jpg"];
    }

    public function testBadSignedUrlIsLoggedAsWarning(): void
    {
        // Anyone can send a runtime filter request with a bad hash.
        $exception = new BadRequestHttpException('Signed url does not pass the sign check for path "media/abc/large.jpg" and filter "thumbnail"');

        $this->subscriber->onKernelException($this->createExceptionEvent('liip_imagine_filter_runtime', $exception));

        $this->assertTrue($this->handler->hasRecordThatMatches('/^Image filter failed$/', Level::Warning));
    }

    public function testOtherRoutesAreIgnored(): void
    {
        $this->subscriber->onKernelException($this->createExceptionEvent('api_media_get_item', new \RuntimeException('unrelated')));

        $this->assertSame([], $this->handler->getRecords());
    }

    private function createExceptionEvent(string $route, \Throwable $throwable, string $path = 'media/abc/large.jpg', string $filter = 'thumbnail'): ExceptionEvent
    {
        // Encoded in the URI as a browser sends it; the router decodes it into the path attribute.
        $request = Request::create('/media/cache/resolve/'.$filter.'/'.implode('/', array_map(rawurlencode(...), explode('/', $path))));
        $request->attributes->add(['_route' => $route, 'filter' => $filter, 'path' => $path]);

        return new ExceptionEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $throwable,
        );
    }
}
