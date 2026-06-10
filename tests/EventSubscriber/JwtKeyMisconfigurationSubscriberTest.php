<?php

declare(strict_types=1);

namespace App\Tests\EventSubscriber;

use App\EventSubscriber\JwtKeyMisconfigurationSubscriber;
use Lcobucci\JWT\Signer\InvalidKeyProvided;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTInvalidEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\InvalidTokenException;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTEncodeFailureException;
use Monolog\Handler\TestHandler;
use Monolog\Level;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class JwtKeyMisconfigurationSubscriberTest extends TestCase
{
    private TestHandler $handler;
    private JwtKeyMisconfigurationSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->handler = new TestHandler();
        $this->subscriber = new JwtKeyMisconfigurationSubscriber(new Logger('auth', [$this->handler]));
    }

    public function testJwtInvalidCausedByUnusableKeyAnswers503AndLogsCritical(): void
    {
        // The chain produced when the key file cannot be parsed:
        // InvalidTokenException ← JWTDecodeFailureException ← InvalidKeyProvided.
        $exception = new InvalidTokenException('Invalid JWT Token', 0, new JWTDecodeFailureException(
            JWTDecodeFailureException::INVALID_TOKEN,
            'Invalid JWT Token',
            InvalidKeyProvided::cannotBeParsed('error:1E08010C:DECODER routines::unsupported'),
        ));
        $event = new JWTInvalidEvent($exception, new JsonResponse(['code' => 401], Response::HTTP_UNAUTHORIZED));

        $this->subscriber->onJwtInvalid($event);

        $response = $event->getResponse();
        $this->assertSame(Response::HTTP_SERVICE_UNAVAILABLE, $response->getStatusCode());
        $this->assertTrue($response->headers->has('Retry-After'));

        $this->assertTrue($this->handler->hasRecordThatMatches('/JWT keys are unusable/', Level::Critical));
        $record = $this->handler->getRecords()[0];
        $this->assertSame('auth.jwt_key_unusable', $record->context['event']);
        $this->assertSame('validate', $record->context['operation']);
        $this->assertSame($exception, $record->context['exception']);
    }

    public function testJwtInvalidCausedByTheTokenItselfKeeps401AndLogsNothing(): void
    {
        $original = new JsonResponse(['code' => 401], Response::HTTP_UNAUTHORIZED);
        $event = new JWTInvalidEvent(
            new InvalidTokenException('Invalid JWT Token', 0, new JWTDecodeFailureException(
                JWTDecodeFailureException::INVALID_TOKEN,
                'Invalid JWT Token',
            )),
            $original,
        );

        $this->subscriber->onJwtInvalid($event);

        $this->assertSame($original, $event->getResponse());
        $this->assertSame([], $this->handler->getRecords());
    }

    public function testEncodeFailureBecomes503AndLogsCritical(): void
    {
        $exception = new JWTEncodeFailureException(
            JWTEncodeFailureException::UNSIGNED_TOKEN,
            'Unable to create a signed JWT from the given configuration.',
        );
        $event = $this->createExceptionEvent($exception);

        $this->subscriber->onKernelException($event);

        $throwable = $event->getThrowable();
        $this->assertInstanceOf(ServiceUnavailableHttpException::class, $throwable);
        $this->assertSame($exception, $throwable->getPrevious());

        $this->assertTrue($this->handler->hasRecordThatMatches('/JWT keys are unusable/', Level::Critical));
        $record = $this->handler->getRecords()[0];
        $this->assertSame('auth.jwt_key_unusable', $record->context['event']);
        $this->assertSame('sign', $record->context['operation']);
        $this->assertSame($exception, $record->context['exception']);
    }

    public function testUnrelatedExceptionIsLeftAloneAndLogsNothing(): void
    {
        $exception = new \RuntimeException('something else');
        $event = $this->createExceptionEvent($exception);

        $this->subscriber->onKernelException($event);

        $this->assertSame($exception, $event->getThrowable());
        $this->assertSame([], $this->handler->getRecords());
    }

    private function createExceptionEvent(\Throwable $throwable): ExceptionEvent
    {
        return new ExceptionEvent(
            $this->createMock(HttpKernelInterface::class),
            Request::create('/v2/authentication/token', Request::METHOD_POST),
            HttpKernelInterface::MAIN_REQUEST,
            $throwable,
        );
    }
}
