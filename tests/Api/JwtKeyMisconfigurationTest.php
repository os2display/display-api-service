<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Entity\User;
use App\Enum\UserTypeEnum;
use App\Tests\AbstractBaseApiTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifies that unusable JWT signing keys (a server-side misconfiguration)
 * surface as "503 Service Unavailable" and never as a false "401 Invalid JWT
 * Token" that blames the client's token.
 *
 * Seen in production during the v2 → v3 upgrade: the JWT key files were not
 * migrated, so the key loader handed lcobucci/jwt the key *path* as if it
 * were the key itself. Parsing failed with OpenSSL "error:1E08010C:DECODER
 * routines::unsupported", every token validation answered 401, and token
 * issuing/refresh answered 500 — making the screen client discard a
 * perfectly good token and log out.
 */
class JwtKeyMisconfigurationTest extends AbstractBaseApiTestCase
{
    // Key paths configured for the test env in
    // config/packages/test/lexik_jwt_authentication.yaml.
    private const array KEY_FILES = [
        'tests/config/jwt/private-test.pem',
        'tests/config/jwt/public-test.pem',
    ];

    /** @var string[] */
    private array $movedKeyFiles = [];

    protected function tearDown(): void
    {
        $this->restoreKeyFiles();

        parent::tearDown();
    }

    public function testJwtAuthenticatedRequestReturns503NotFalse401WhenJwtKeysAreMissing(): void
    {
        // Mint a perfectly valid JWT while the keys are still in place.
        self::createClient();

        $user = new User();
        $user->setProviderId('test@example.com');
        $user->setEmail('test@example.com');
        $user->setFullName('Test Test');
        $user->setUserType(UserTypeEnum::USERNAME_PASSWORD);

        $token = $this->getJwtToken($user);

        $this->removeKeyFiles();

        $client = self::createClient();
        $response = $client->request('GET', '/v2/layouts', [
            'auth_bearer' => $token,
            'headers' => ['Content-Type' => 'application/ld+json'],
        ]);

        $statusCode = $response->getStatusCode();

        $this->assertNotSame(
            Response::HTTP_UNAUTHORIZED,
            $statusCode,
            'Missing/unusable JWT keys are a server-side misconfiguration and must not surface as a false 401 — that logs out clients whose tokens are fine.'
        );
        $this->assertSame(
            Response::HTTP_SERVICE_UNAVAILABLE,
            $statusCode,
            'Missing/unusable JWT keys should surface as 503 Service Unavailable.'
        );
        $this->assertResponseHasHeader('Retry-After', 'A 503 caused by unusable JWT keys should tell clients when to retry.');
    }

    public function testLoginWithValidCredentialsReturns503WhenJwtKeysAreMissing(): void
    {
        $this->removeKeyFiles();

        // Valid fixture credentials: authentication succeeds against the
        // database, then signing the JWT fails because the keys are gone.
        $client = self::createClient();
        $response = $client->request('POST', '/v2/authentication/token', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => [
                'providerId' => 'admin@example.com',
                'password' => 'apassword',
            ],
        ]);

        $this->assertSame(
            Response::HTTP_SERVICE_UNAVAILABLE,
            $response->getStatusCode(),
            'Failing to sign a JWT because of missing/unusable keys should surface as 503 Service Unavailable, not a generic 500.'
        );
        $this->assertResponseHasHeader('Retry-After', 'A 503 caused by unusable JWT keys should tell clients when to retry.');
    }

    private function removeKeyFiles(): void
    {
        // Boot a fresh kernel afterwards so nothing has the keys cached.
        self::ensureKernelShutdown();

        $projectDir = \dirname(__DIR__, 2);

        foreach (self::KEY_FILES as $keyFile) {
            $path = $projectDir.'/'.$keyFile;
            self::assertFileExists($path);
            self::assertTrue(rename($path, $path.'.moved-away'), sprintf('Could not move key file "%s" out of the way.', $path));
            $this->movedKeyFiles[] = $path;
        }
    }

    private function restoreKeyFiles(): void
    {
        foreach ($this->movedKeyFiles as $path) {
            if (file_exists($path.'.moved-away')) {
                rename($path.'.moved-away', $path);
            }
        }

        $this->movedKeyFiles = [];
    }
}
