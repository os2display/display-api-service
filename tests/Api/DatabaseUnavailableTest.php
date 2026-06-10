<?php

declare(strict_types=1);

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\User;
use App\Enum\UserTypeEnum;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifies that an unreachable database surfaces as "503 Service Unavailable"
 * and never as a false "401 Unauthorized".
 *
 * A 401 makes API clients (e.g. the screen client) discard their tokens and
 * log out, so a database outage must not bubble up through the JWT
 * authenticator as an authentication failure.
 */
class DatabaseUnavailableTest extends ApiTestCase
{
    // Port 1 on localhost is never listening, so the connection attempt
    // fails immediately with "connection refused".
    private const UNREACHABLE_DATABASE_URL = 'mysql://root:password@127.0.0.1:1/db_test?serverVersion=11.4.4-MariaDB';

    private ?string $originalEnvDatabaseUrl = null;
    private ?string $originalServerDatabaseUrl = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalEnvDatabaseUrl = $_ENV['DATABASE_URL'] ?? null;
        $this->originalServerDatabaseUrl = $_SERVER['DATABASE_URL'] ?? null;

        $_ENV['DATABASE_URL'] = self::UNREACHABLE_DATABASE_URL;
        $_SERVER['DATABASE_URL'] = self::UNREACHABLE_DATABASE_URL;
    }

    protected function tearDown(): void
    {
        if (null === $this->originalEnvDatabaseUrl) {
            unset($_ENV['DATABASE_URL']);
        } else {
            $_ENV['DATABASE_URL'] = $this->originalEnvDatabaseUrl;
        }

        if (null === $this->originalServerDatabaseUrl) {
            unset($_SERVER['DATABASE_URL']);
        } else {
            $_SERVER['DATABASE_URL'] = $this->originalServerDatabaseUrl;
        }

        parent::tearDown();
    }

    public function testJwtAuthenticatedRequestReturns503NotFalse401WhenDatabaseIsUnreachable(): void
    {
        $client = self::createClient();

        // Create a valid JWT without touching the database. The signing keys
        // are file based, so this works even though the database is down.
        $user = new User();
        $user->setProviderId('test@example.com');
        $user->setEmail('test@example.com');
        $user->setFullName('Test Test');
        $user->setUserType(UserTypeEnum::USERNAME_PASSWORD);

        $token = self::getContainer()->get(JWTTokenManagerInterface::class)->create($user);

        $response = $client->request('GET', '/v2/layouts', [
            'auth_bearer' => $token,
            'headers' => ['Content-Type' => 'application/ld+json'],
        ]);

        $statusCode = $response->getStatusCode();

        $this->assertNotSame(
            Response::HTTP_UNAUTHORIZED,
            $statusCode,
            'An unreachable database must not surface as a false 401 — that logs out otherwise authenticated clients.'
        );
        $this->assertSame(
            Response::HTTP_SERVICE_UNAVAILABLE,
            $statusCode,
            'An unreachable database should surface as 503 Service Unavailable.'
        );
        $this->assertResponseHasHeader('Retry-After', 'A 503 caused by a database outage should tell clients when to retry.');
    }

    public function testLoginRequestReturns503NotFalse401WhenDatabaseIsUnreachable(): void
    {
        $client = self::createClient();

        $response = $client->request('POST', '/v2/authentication/token', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => [
                'providerId' => 'test@example.com',
                'password' => 'password',
            ],
        ]);

        $statusCode = $response->getStatusCode();

        $this->assertNotSame(
            Response::HTTP_UNAUTHORIZED,
            $statusCode,
            'An unreachable database must not surface as a false 401 "bad credentials" on login.'
        );
        $this->assertSame(
            Response::HTTP_SERVICE_UNAVAILABLE,
            $statusCode,
            'An unreachable database should surface as 503 Service Unavailable.'
        );
    }
}
