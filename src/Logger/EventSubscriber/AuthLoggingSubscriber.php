<?php

declare(strict_types=1);

namespace App\Logger\EventSubscriber;

use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTExpiredEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTFailureEventInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTInvalidEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTNotFoundEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events as LexikEvents;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;

/**
 * Logs security outcomes on the `auth` channel.
 *
 * This subscriber only logs — it does not alter authentication behaviour. The
 * JWT payload/tenant injection lives in the dedicated Lexik listeners
 * (AuthenticationSuccessListener, JWTCreatedListener, JwtTokenRefreshedSubscriber)
 * and must not be duplicated here.
 *
 * Token strings and credentials are never logged; only identifiers, firewall
 * names and exception messages are.
 */
final readonly class AuthLoggingSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private LoggerInterface $authLogger,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
            LoginFailureEvent::class => 'onLoginFailure',
            LogoutEvent::class => 'onLogout',
            LexikEvents::JWT_INVALID => 'onJwtFailure',
            LexikEvents::JWT_EXPIRED => 'onJwtFailure',
            LexikEvents::JWT_NOT_FOUND => 'onJwtFailure',
        ];
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $this->guard(fn () => $this->authLogger->info('Authentication succeeded', [
            'event' => 'auth.login_success',
            'user_identifier' => $event->getUser()->getUserIdentifier(),
            'firewall' => $event->getFirewallName(),
            'authenticator' => $event->getAuthenticator()::class,
        ]));
    }

    public function onLoginFailure(LoginFailureEvent $event): void
    {
        $this->guard(fn () => $this->authLogger->warning('Authentication failed', [
            'event' => 'auth.login_failure',
            'firewall' => $event->getFirewallName(),
            'authenticator' => $event->getAuthenticator()::class,
            'exception' => $event->getException(),
        ]));
    }

    public function onLogout(LogoutEvent $event): void
    {
        $this->guard(fn () => $this->authLogger->info('User logged out', [
            'event' => 'auth.logout',
            'user_identifier' => $event->getToken()?->getUserIdentifier(),
        ]));
    }

    public function onJwtFailure(JWTFailureEventInterface $event): void
    {
        $this->guard(fn () => $this->authLogger->warning('JWT authentication failed', [
            'event' => 'auth.jwt_failure',
            'reason' => match (true) {
                $event instanceof JWTInvalidEvent => 'invalid',
                $event instanceof JWTExpiredEvent => 'expired',
                $event instanceof JWTNotFoundEvent => 'not_found',
                default => 'unknown',
            },
            'exception' => $event->getException(),
        ]));
    }

    /**
     * Runs a logging closure, swallowing any failure.
     *
     * This subscriber observes authentication events; logging must never break
     * them. Both the context building (identity accessors that can throw) and the
     * write itself run inside the guard, so a logging failure drops the line
     * rather than aborting login/logout.
     */
    private function guard(callable $log): void
    {
        try {
            $log();
        } catch (\Throwable) { // @phpstan-ignore logging.silentCatch (this subscriber observes auth events; logging must never break login/logout, so a logging failure drops the line — see method doc)
            // Intentionally silent — see method doc.
        }
    }
}
