<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Lcobucci\JWT\Signer\CannotSignPayload;
use Lcobucci\JWT\Signer\InvalidKeyProvided;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTInvalidEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTEncodeFailureException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Surfaces unusable JWT signing keys as "503 Service Unavailable" instead of
 * a false "401 Invalid JWT Token" (validation) or a generic 500 (issuing).
 *
 * When the configured JWT key pair cannot be loaded (e.g. key files lost in
 * a deployment, yielding an OpenSSL "DECODER routines::unsupported" parse
 * failure), every token validation fails. That is a server-side
 * misconfiguration, not a problem with the client's token — answering 401
 * makes clients (e.g. the screen client) discard perfectly good tokens and
 * log out. A 503 with a Retry-After header tells them the outage is
 * temporary: retry later, do not re-authenticate.
 *
 * Two interception points are needed:
 *
 * - Token validation: Lexik builds the 401 response inside its authenticator
 *   without throwing to the kernel, so the 401 → 503 swap must happen on
 *   Lexik's JWT_INVALID event.
 * - Token issuing (login, refresh): signing failures escape as uncaught
 *   JWTEncodeFailureException, handled on kernel.exception.
 *
 * Logs at `critical` on the `auth` channel (ADR 011): with unusable keys the
 * authentication subsystem is down for every client.
 */
class JwtKeyMisconfigurationSubscriber implements EventSubscriberInterface
{
    final public const int RETRY_AFTER_SECONDS = 10;

    public function __construct(
        private readonly LoggerInterface $authLogger,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            Events::JWT_INVALID => 'onJwtInvalid',
            // Positive priority so this runs before the framework's
            // exception listeners render a response from the original
            // throwable.
            KernelEvents::EXCEPTION => ['onKernelException', 50],
        ];
    }

    public function onJwtInvalid(JWTInvalidEvent $event): void
    {
        // A token rejected because of the token itself (bad signature,
        // malformed, expired) must stay a 401. Only a key that cannot be
        // loaded at all marks the service as unable to validate any token.
        for ($e = $event->getException(); null !== $e; $e = $e->getPrevious()) {
            if ($e instanceof InvalidKeyProvided || $e instanceof CannotSignPayload) {
                $this->authLogger->critical('JWT keys are unusable, answering 503 instead of 401 on token validation', [
                    'event' => 'auth.jwt_key_unusable',
                    'operation' => 'validate',
                    'exception' => $event->getException(),
                ]);

                $event->setResponse(new JsonResponse(
                    [
                        'code' => Response::HTTP_SERVICE_UNAVAILABLE,
                        'message' => 'Service temporarily unavailable. Please try again later.',
                    ],
                    Response::HTTP_SERVICE_UNAVAILABLE,
                    ['Retry-After' => self::RETRY_AFTER_SECONDS],
                ));

                return;
            }
        }
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $throwable = $event->getThrowable();

        // Failing to *sign* a token is always a key/configuration problem on
        // the server, never a client error. The failure may be wrapped by
        // other layers, so walk the exception chain.
        for ($e = $throwable; null !== $e; $e = $e->getPrevious()) {
            if ($e instanceof JWTEncodeFailureException || $e instanceof InvalidKeyProvided || $e instanceof CannotSignPayload) {
                $this->authLogger->critical('JWT keys are unusable, answering 503 on token signing', [
                    'event' => 'auth.jwt_key_unusable',
                    'operation' => 'sign',
                    'exception' => $throwable,
                ]);

                $event->setThrowable(new ServiceUnavailableHttpException(
                    self::RETRY_AFTER_SECONDS,
                    'Service temporarily unavailable. Please try again later.',
                    $throwable,
                ));

                return;
            }
        }
    }
}
