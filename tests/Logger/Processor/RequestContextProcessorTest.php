<?php

declare(strict_types=1);

namespace App\Tests\Logger\Processor;

use App\Entity\ScreenUser;
use App\Entity\Tenant;
use App\Entity\Tenant\Screen;
use App\Entity\User;
use App\Logger\Processor\RequestContextProcessor;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Uid\Ulid;

class RequestContextProcessorTest extends TestCase
{
    public function testNoRequestAndNoUserLeavesRecordUnenriched(): void
    {
        $processor = new RequestContextProcessor(new RequestStack(), $this->security(null));

        $record = $processor($this->record());

        $this->assertArrayNotHasKey('request_id', $record->extra);
        $this->assertArrayNotHasKey('route', $record->extra);
        $this->assertArrayNotHasKey('user_id', $record->extra);
        $this->assertArrayNotHasKey('screen_id', $record->extra);
    }

    public function testRequestContextIsAttached(): void
    {
        $request = Request::create('/v2/screens', Request::METHOD_POST);
        $request->attributes->set('_request_id', 'abc123def4567890abc123def4567890');
        $request->attributes->set('_route', 'api_screens_post_collection');
        $stack = new RequestStack();
        $stack->push($request);

        $processor = new RequestContextProcessor($stack, $this->security(null));

        $record = $processor($this->record());

        // 32-char hex request id accepted verbatim — no reformatting/rejection.
        $this->assertSame('abc123def4567890abc123def4567890', $record->extra['request_id']);
        $this->assertSame('api_screens_post_collection', $record->extra['route']);
        $this->assertSame('POST', $record->extra['method']);
    }

    public function testScreenUserPopulatesScreenIdAndTenantNotUserId(): void
    {
        $tenant = $this->createMock(Tenant::class);
        $tenant->method('getTenantKey')->willReturn('Example1');

        $screen = $this->createMock(Screen::class);
        $screen->method('getId')->willReturn(new Ulid());

        $user = $this->createMock(ScreenUser::class);
        $user->method('getScreen')->willReturn($screen);
        $user->method('getActiveTenant')->willReturn($tenant);

        $processor = new RequestContextProcessor(new RequestStack(), $this->security($user));

        $record = $processor($this->record());

        $this->assertArrayHasKey('screen_id', $record->extra);
        $this->assertArrayNotHasKey('user_id', $record->extra);
        $this->assertSame('Example1', $record->extra['tenant_id']);
    }

    public function testBackOfficeUserPopulatesUserIdNotScreenId(): void
    {
        $tenant = $this->createMock(Tenant::class);
        $tenant->method('getTenantKey')->willReturn('Example1');

        $user = $this->createMock(User::class);
        $user->method('getUserIdentifier')->willReturn('editor@example.com');
        $user->method('getActiveTenant')->willReturn($tenant);

        $processor = new RequestContextProcessor(new RequestStack(), $this->security($user));

        $record = $processor($this->record());

        $this->assertSame('editor@example.com', $record->extra['user_id']);
        $this->assertArrayNotHasKey('screen_id', $record->extra);
        $this->assertSame('Example1', $record->extra['tenant_id']);
    }

    public function testActiveTenantResolutionFailureDoesNotThrow(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getUserIdentifier')->willReturn('editor@example.com');
        $user->method('getActiveTenant')->willThrowException(new \InvalidArgumentException('no tenant'));

        $processor = new RequestContextProcessor(new RequestStack(), $this->security($user));

        $record = $processor($this->record());

        $this->assertSame('editor@example.com', $record->extra['user_id']);
        $this->assertArrayNotHasKey('tenant_id', $record->extra);
    }

    private function record(): LogRecord
    {
        return new LogRecord(new \DateTimeImmutable('2026-06-02T00:00:00+00:00'), 'screen', Level::Info, 'test');
    }

    private function security(?object $user): Security
    {
        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn($user);

        return $security;
    }
}
