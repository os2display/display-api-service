<?php

declare(strict_types=1);

namespace App\Security\EventListener;

use Gesdinet\JWTRefreshTokenBundle\Event\RefreshAuthenticationFailureEvent;
use Gesdinet\JWTRefreshTokenBundle\Event\RefreshTokenNotFoundEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationFailureEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTExpiredEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTInvalidEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTNotFoundEvent;
use Psr\Log\LoggerInterface;

/**
 * Class AuthenticationFailureListener.
 *
 * Class is responsible for logging authentication failure event details to aid in debugging screen clients that
 * loose authentication.
 */
readonly class AuthenticationFailureListener
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    public function onAuthenticationFailure(AuthenticationFailureEvent $event): void
    {
        $this->logJWTAuthenticationEvent($event);
    }

    public function onJwtInvalid(JWTInvalidEvent $event): void
    {
        $this->logJWTAuthenticationEvent($event);
    }

    public function onJwtNotFound(JWTNotFoundEvent $event): void
    {
        $this->logJWTAuthenticationEvent($event);
    }

    public function onJwtExpired(JWTExpiredEvent $event): void
    {
        $this->logJWTAuthenticationEvent($event);
    }

    public function onRefreshTokenFailure(RefreshAuthenticationFailureEvent $event): void
    {
        $this->logJWTRefreshEvent($event);
    }

    public function onRefreshTokenNotFound(RefreshTokenNotFoundEvent $event): void
    {
        $this->logJWTRefreshEvent($event);
    }

    private function logJWTAuthenticationEvent(AuthenticationFailureEvent $event): void
    {
        $request = $event->getRequest();
        $exception = $event->getException();

        $data = [];
        $data['AuthenticationFailureListener'] = \get_class($event);
        $data['request']['clientIp'] = $request->getClientIp();
        $data['request']['pathInfo'] = $request->getPathInfo();
        $data['request']['requestUri'] = $request->getRequestUri();
        $data['request']['method'] = $request->getMethod();
        $data['request']['referer'] = $request->headers->get('referer');

        $this->logger->error('AuthenticationFailureListener', [
            'code' => $exception->getCode(),
            'message' => $exception->getMessage(),
            'request' => $data,
        ]);
    }

    private function logJWTRefreshEvent(RefreshAuthenticationFailureEvent|RefreshTokenNotFoundEvent $event): void
    {
        $exception = $event->getException();

        $data = [];
        $data['AuthenticationFailureListener'] = \get_class($event);
        $data['request'] = 'Not available for JWT refresh failure events';

        $this->logger->error('AuthenticationFailureListener', [
            'code' => $exception->getCode(),
            'message' => $exception->getMessage(),
            'request' => $data,
        ]);
    }
}
