<?php

declare(strict_types=1);

namespace App\Tests\Logger\EventSubscriber;

use App\Logger\EventSubscriber\RequestIdSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class RequestIdSubscriberTest extends TestCase
{
    private const HEADER = 'X-Request-Id';
    private const ATTR = '_request_id';

    public function testInboundHeaderIsAdoptedAndEchoedOnResponse(): void
    {
        $subscriber = new RequestIdSubscriber();
        $request = Request::create('/');
        $request->headers->set(self::HEADER, 'upstream-id-123');

        $subscriber->onRequest($this->requestEvent($request));
        $this->assertSame('upstream-id-123', $request->attributes->get(self::ATTR));

        $response = new Response();
        $subscriber->onResponse($this->responseEvent($request, $response));
        $this->assertSame('upstream-id-123', $response->headers->get(self::HEADER));
    }

    public function testIdIsMintedWhenNoInboundHeaderAndEchoedOnResponse(): void
    {
        $subscriber = new RequestIdSubscriber();
        $request = Request::create('/');

        $subscriber->onRequest($this->requestEvent($request));
        $minted = $request->attributes->get(self::ATTR);
        $this->assertIsString($minted);
        $this->assertNotSame('', $minted);

        $response = new Response();
        $subscriber->onResponse($this->responseEvent($request, $response));
        $this->assertSame($minted, $response->headers->get(self::HEADER));
    }

    public function testEmptyInboundHeaderMintsAFreshId(): void
    {
        $subscriber = new RequestIdSubscriber();
        $request = Request::create('/');
        $request->headers->set(self::HEADER, '');

        $subscriber->onRequest($this->requestEvent($request));

        // `?:` treats the empty header as absent and mints a non-empty id.
        $this->assertNotSame('', $request->attributes->get(self::ATTR));
    }

    public function testResponseHeaderIsNotOverwrittenWhenAlreadySet(): void
    {
        $subscriber = new RequestIdSubscriber();
        $request = Request::create('/');
        $request->attributes->set(self::ATTR, 'our-id');

        $response = new Response();
        $response->headers->set(self::HEADER, 'downstream-id');
        $subscriber->onResponse($this->responseEvent($request, $response));

        $this->assertSame('downstream-id', $response->headers->get(self::HEADER));
    }

    public function testSubRequestIsIgnored(): void
    {
        $subscriber = new RequestIdSubscriber();
        $request = Request::create('/');

        $subscriber->onRequest($this->requestEvent($request, HttpKernelInterface::SUB_REQUEST));
        $this->assertNull($request->attributes->get(self::ATTR));

        $request->attributes->set(self::ATTR, 'main-id');
        $response = new Response();
        $subscriber->onResponse($this->responseEvent($request, $response, HttpKernelInterface::SUB_REQUEST));
        $this->assertFalse($response->headers->has(self::HEADER));
    }

    private function requestEvent(Request $request, int $type = HttpKernelInterface::MAIN_REQUEST): RequestEvent
    {
        return new RequestEvent($this->createMock(HttpKernelInterface::class), $request, $type);
    }

    private function responseEvent(Request $request, Response $response, int $type = HttpKernelInterface::MAIN_REQUEST): ResponseEvent
    {
        return new ResponseEvent($this->createMock(HttpKernelInterface::class), $request, $type, $response);
    }
}
