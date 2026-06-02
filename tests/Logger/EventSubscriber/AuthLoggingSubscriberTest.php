<?php

declare(strict_types=1);

namespace App\Tests\Logger\EventSubscriber;

use App\Logger\EventSubscriber\AuthLoggingSubscriber;
use Monolog\Handler\TestHandler;
use Monolog\Level;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class AuthLoggingSubscriberTest extends TestCase
{
    public function testLoginFailureEmitsAuthWarningWithoutCredentials(): void
    {
        $handler = new TestHandler();
        $subscriber = new AuthLoggingSubscriber(new Logger('auth', [$handler]));

        $event = new LoginFailureEvent(
            new BadCredentialsException('Invalid credentials.'),
            $this->createMock(AuthenticatorInterface::class),
            Request::create('/v2/authentication/token', Request::METHOD_POST, [], [], [], [], '{"password":"s3cr3t"}'),
            null,
            'main',
        );

        $subscriber->onLoginFailure($event);

        $this->assertTrue($handler->hasWarningRecords());
        $records = $handler->getRecords();
        $this->assertCount(1, $records);
        $record = $records[0];
        $this->assertSame(Level::Warning, $record->level);
        $this->assertSame('auth.login_failure', $record->context['event']);
        $this->assertSame('main', $record->context['firewall']);

        // No credential material anywhere in the serialised record.
        $this->assertStringNotContainsStringIgnoringCase('s3cr3t', json_encode($record->toArray(), JSON_THROW_ON_ERROR));
    }

    public function testLogoutEmitsInfoWithUserIdentifier(): void
    {
        $handler = new TestHandler();
        $subscriber = new AuthLoggingSubscriber(new Logger('auth', [$handler]));

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUserIdentifier')->willReturn('editor@example.com');

        $subscriber->onLogout(new LogoutEvent(Request::create('/'), $token));

        $this->assertTrue($handler->hasInfoRecords());
        $record = $handler->getRecords()[0];
        $this->assertSame('auth.logout', $record->context['event']);
        $this->assertSame('editor@example.com', $record->context['user_identifier']);
    }
}
