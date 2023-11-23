<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Entity\Tenant\Screen;
use App\Tests\AbstractBaseApiTestCase;
use Gesdinet\JWTRefreshTokenBundle\Doctrine\RefreshTokenManager;

class AuthenticationScreenTest extends AbstractBaseApiTestCase
{
    // .env.test:JWT_SCREEN_TOKEN_TTL=43200 # 12 hours
    public const ENV_JWT_SCREEN_TOKEN_TTL = 43200;

    // .env.test:JWT_SCREEN_REFRESH_TOKEN_TTL=86400 # 24 hours
    public const ENV_JWT_SCREEN_REFRESH_TOKEN_TTL = 86400;

    public function testBindKeyUnchanged(): void
    {
        $screenClient = self::createClient();
        // Disable kernel reboot between requests to avoid session data reset.
        $screenClient->disableReboot();

        $request1 = $screenClient->request('POST', '/v1/authentication/screen', [
            'headers' => ['Content-Type' => 'application/json'],
        ]);

        $content1 = json_decode($request1->getContent());

        $this->assertSame(200, $request1->getStatusCode());
        $this->assertEquals(8, strlen($content1->bindKey));
        $this->assertEquals('awaitingBindKey', $content1->status);

        $request2 = $screenClient->request('POST', '/v1/authentication/screen', [
            'headers' => ['Content-Type' => 'application/json'],
        ]);

        $content2 = json_decode($request2->getContent());
        $this->assertEquals(
            $content1->bindKey,
            $content2->bindKey,
            'Bind key should not change within the same http session'
        );
    }

    public function testScreenBindSuccess(): void
    {
        // Create clients first because it results in kernel boot and thereby
        // session data reset
        $screenClient = self::createClient();
        $adminClient = $this->getAuthenticatedClient('ROLE_ADMIN');

        // Disable kernel reboots between requests to avoid session data reset.
        $screenClient->disableReboot();
        $adminClient->disableReboot();

        // Step 1 (Screen):
        // Screen client is started. Client is unauthenticated and receives bind key.
        $response1 = $screenClient->request('POST', '/v1/authentication/screen', [
            'headers' => ['Content-Type' => 'application/json'],
        ]);
        $content1 = json_decode($response1->getContent());
        $this->assertEquals(8, strlen($content1->bindKey));
        $this->assertEquals('awaitingBindKey', $content1->status);

        // Step 2 (Admin):
        // Editor binds key to screen through admin app
        $screenIri = $this->findIriBy(Screen::class, ['tenant' => $this->tenant]);
        $response2 = $adminClient->request('POST', $screenIri.'/bind', [
            'json' => [
                'bindKey' => $content1->bindKey,
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);

        $this->assertEquals(204, $response2->getStatusCode());

        // Step 3 (Screen):
        // Client has been bound by admin and receives tokens.
        $time = new \DateTimeImmutable();
        $response3 = $screenClient->request('POST', '/v1/authentication/screen', [
            'headers' => ['Content-Type' => 'application/json'],
        ]);
        $content3 = json_decode($response3->getContent());

        // Assert JWT Token values
        $this->assertNotEmpty($content3->token);
        $decoded = $this->decodeJwt($content3->token);
        $expected = $time->add(new \DateInterval('PT'.self::ENV_JWT_SCREEN_TOKEN_TTL.'S'));
        $this->assertEqualsWithDelta(
            $expected->getTimestamp(),
            $decoded->exp,
            1.0,
            'JWT expire does not match expected value  (NOW + JWT_SCREEN_TOKEN_TTL)'
        );

        // Assert Refresh token values
        $this->assertNotEmpty($content3->refresh_token);
        $expected = $time->add(new \DateInterval('PT'.self::ENV_JWT_SCREEN_REFRESH_TOKEN_TTL.'S'));
        $this->assertEqualsWithDelta(
            $expected->getTimestamp(),
            $content3->refresh_token_expiration,
            1.0,
            'refresh_token_expiration does not match expected value  (NOW + ENV_JWT_SCREEN_REFRESH_TOKEN_TTL)'
        );
        $this->assertEquals(self::ENV_JWT_SCREEN_REFRESH_TOKEN_TTL, $content3->refresh_token_ttl);
        $this->assertEquals('ready', $content3->status);
        $this->assertStringEndsWith($content3->screenId, $screenIri);

        // Step 4 (Screen):
        // Refresh jwt and refresh token
        $time = new \DateTimeImmutable();
        $response4 = $screenClient->request('POST', '/v1/authentication/token/refresh', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => [
                'refresh_token' => $content3->refresh_token,
            ],
        ]);
        $content4 = json_decode($response4->getContent());
        $this->assertNotEmpty($content4->token);
        $this->assertNotEmpty($content4->refresh_token);

        $expected = $time->add(new \DateInterval('PT'.self::ENV_JWT_SCREEN_REFRESH_TOKEN_TTL.'S'));
        $this->assertEqualsWithDelta(
            $expected->getTimestamp(),
            $content4->refresh_token_expiration,
            1.0,
            'Response body: Received refresh token expiration does not match expected value (NOW + JWT_SCREEN_REFRESH_TOKEN_TTL)'
        );

        /** @var RefreshTokenManager $manager */
        $manager = static::getContainer()->get('gesdinet.jwtrefreshtoken.refresh_token_manager');
        $refreshToken = $manager->get($content4->refresh_token);
        $this->assertEqualsWithDelta(
            $content4->refresh_token_expiration,
            $refreshToken->getValid()->getTimestamp(),
            1.0,
            'RefreshTokenManager: Loaded refresh token expiration does not match expected value (NOW + JWT_SCREEN_REFRESH_TOKEN_TTL)'
        );
    }

    public function testScreenBindFailure(): void
    {
        $adminClient = $this->getAuthenticatedClient('ROLE_ADMIN');
        $adminClient->disableReboot();
        $screenIri = $this->findIriBy(Screen::class, ['tenant' => $this->tenant]);

        $response2 = $adminClient->request('POST', $screenIri.'/bind', [
            'json' => [
                'bindKey' => '12345678',
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);

        $this->assertEquals(400, $response2->getStatusCode());
    }

    /**
     * Decode JWT for debugging purposes. Does not verify signature.
     *
     * @see https://www.converticacommerce.com/support-maintenance/security/php-one-liner-decode-jwt-json-web-tokens/
     *
     * @param string $token
     *
     * @return object
     *
     * @throws \JsonException
     */
    private function decodeJwt(string $token): object
    {
        return json_decode(
            base64_decode(str_replace('_', '/', str_replace('-', '+', explode('.', $token)[1]))),
            false,
            512,
            JSON_THROW_ON_ERROR
        );
    }
}
