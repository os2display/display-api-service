<?php

declare(strict_types=1);

namespace App\Tests\Logger\EventSubscriber;

use App\Logger\EventSubscriber\AuthLoggingSubscriber;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTExpiredEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTFailureEventInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTInvalidEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTNotFoundEvent;
use Monolog\Handler\TestHandler;
use Monolog\Level;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class AuthLoggingSubscriberTest extends TestCase
{
    public function testLoginSuccessEmitsInfoWithIdentifierFirewallAndAuthenticator(): void
    {
        $handler = new TestHandler();
        $subscriber = new AuthLoggingSubscriber(new Logger('auth', [$handler]));

        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn('editor@example.com');
        $authenticator = $this->createMock(AuthenticatorInterface::class);

        $event = new LoginSuccessEvent(
            $authenticator,
            new SelfValidatingPassport(new UserBadge('editor@example.com', fn () => $user)),
            $this->createMock(TokenInterface::class),
            Request::create('/v2/authentication/token', Request::METHOD_POST),
            null,
            'main',
        );

        $subscriber->onLoginSuccess($event);

        $this->assertTrue($handler->hasInfoRecords());
        $record = $handler->getRecords()[0];
        $this->assertSame('auth.login_success', $record->context['event']);
        $this->assertSame('editor@example.com', $record->context['user_identifier']);
        $this->assertSame('main', $record->context['firewall']);
        $this->assertSame($authenticator::class, $record->context['authenticator']);
    }

    /**
     * The `reason` string is what operators key dashboards on, and the three
     * concrete Lexik events all share JWTFailureEventInterface — an easy arm to
     * mis-wire — so pin each mapping, including the `unknown` default.
     */
    public function testJwtFailureMapsEachEventTypeToReason(): void
    {
        $exception = new AuthenticationException('nope');
        $cases = [
            'invalid' => new JWTInvalidEvent($exception, null),
            'expired' => new JWTExpiredEvent($exception, null),
            'not_found' => new JWTNotFoundEvent($exception, null),
            'unknown' => $this->unknownFailureEvent($exception),
        ];

        foreach ($cases as $expectedReason => $event) {
            $handler = new TestHandler();
            $subscriber = new AuthLoggingSubscriber(new Logger('auth', [$handler]));

            $subscriber->onJwtFailure($event);

            $this->assertTrue($handler->hasWarningRecords(), "reason=$expectedReason");
            $record = $handler->getRecords()[0];
            $this->assertSame('auth.jwt_failure', $record->context['event']);
            $this->assertSame($expectedReason, $record->context['reason']);
        }
    }

    private function unknownFailureEvent(AuthenticationException $exception): JWTFailureEventInterface
    {
        $event = $this->createMock(JWTFailureEventInterface::class);
        $event->method('getException')->willReturn($exception);

        return $event;
    }

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
