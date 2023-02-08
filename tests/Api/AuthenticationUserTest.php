<?php

namespace App\Tests\Api;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Tenant;
use App\Entity\User;
use App\Entity\UserRoleTenant;
use App\Security\TenantScopedAuthenticator;
use Hautelook\AliceBundle\PhpUnit\ReloadDatabaseTrait;

class AuthenticationUserTest extends ApiTestCase
{
    // .env:JWT_TOKEN_TTL=3600
    public const ENV_JWT_TOKEN_TTL = 3600;

    // .env:JWT_REFRESH_TOKEN_TTL=7200
    public const ENV_JWT_REFRESH_TOKEN_TTL = 7200;

    use ReloadDatabaseTrait;

    public function testLogin(): void
    {
        $client = self::createClient();
        $manager = self::getContainer()->get('doctrine')->getManager();

        $tenant = $manager->getRepository(Tenant::class)->findOneBy(['tenantKey' => 'ABC']);

        $user = new User();
        $user->setFullName('Test Test');
        $user->setEmail('test@example.com');
        $user->setProvider(self::class);
        $user->setPassword(
            self::getContainer()->get('security.user_password_hasher')->hashPassword($user, '$3CR3T')
        );
        $user->setProvider('Test');

        $userRoleTenant = new UserRoleTenant();
        $userRoleTenant->setTenant($tenant);
        $userRoleTenant->setRoles(['ROLE_EDITOR']);

        $user->addUserRoleTenant($userRoleTenant);

        $manager->persist($user);
        $manager->flush();

        $time = time();

        // retrieve a token
        $response = $client->request('POST', '/v1/authentication/token', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => [
                'email' => 'test@example.com',
                'password' => '$3CR3T',
            ],
        ]);

        $content = json_decode($response->getContent());
        $this->assertResponseIsSuccessful();
        $this->assertNotEmpty($content->token);
        $this->assertNotEmpty($content->refresh_token);
        $this->assertNotEmpty($content->refresh_token_expiration);
        $decoded = json_decode(base64_decode(str_replace('_', '/', str_replace('-', '+', explode('.', $content->token)[1]))));

        // Assert token ttl values
        $expectedJwt = $decoded->iat + self::ENV_JWT_TOKEN_TTL;
        $this->assertEquals($expectedJwt, $decoded->exp);

        $expectedRefresh = $decoded->iat + self::ENV_JWT_REFRESH_TOKEN_TTL;
        $this->assertEqualsWithDelta($expectedRefresh, $content->refresh_token_expiration, 1.0);

        // test unauthorized if token not set
        $client->request('GET', '/v1/slides');
        $this->assertResponseStatusCodeSame(401);

        // test unauthorized if wrong token set
        $client->request('GET', '/v1/slides', ['auth_bearer' => 'no-token']);
        $this->assertResponseStatusCodeSame(401);

        // test unauthorized if wrong tenant set
        $client->request('GET', '/v1/slides', [
            'auth_bearer' => 'no-token',
            'headers' => [
                TenantScopedAuthenticator::AUTH_TENANT_ID_HEADER => 'XYZ',
            ],
        ]);
        $this->assertResponseStatusCodeSame(401);

        // test authorized without tenant Default to first tenant in users tenant list)
        $client->request('GET', '/v1/slides', ['auth_bearer' => $content->token]);
        $this->assertResponseIsSuccessful();

        // test authorized without tenant (default to first tenant in users tenant list)
        $client->request('GET', '/v1/slides', [
            'auth_bearer' => $content->token,
            'headers' => [
                TenantScopedAuthenticator::AUTH_TENANT_ID_HEADER => 'ABC',
            ],
        ]);
        $this->assertResponseIsSuccessful();
    }
}
