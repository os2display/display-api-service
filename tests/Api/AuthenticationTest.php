<?php

namespace App\Tests\Api;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Tenant;
use App\Entity\User;
use App\Entity\UserRoleTenant;
use App\Security\TenantScopedAuthenticator;
use Hautelook\AliceBundle\PhpUnit\ReloadDatabaseTrait;

class AuthenticationTest extends ApiTestCase
{
    use ReloadDatabaseTrait;

    public function testLogin(): void
    {
        $client = self::createClient();
        $manager = self::$container->get('doctrine')->getManager();

        $tenant = $manager->getRepository(Tenant::class)->findOneBy(['tenantKey' => 'ABC']);

        $user = new User();
        $user->setFullName('Test Test');
        $user->setEmail('test@example.com');
        $user->setProvider(self::class);
        $user->setPassword(
            self::$container->get('security.user_password_hasher')->hashPassword($user, '$3CR3T')
        );

        $userRoleTenant = new UserRoleTenant();
        $userRoleTenant->setTenant($tenant);
        $userRoleTenant->setRoles(['ROLE_EDITOR']);

        $user->addUserRoleTenant($userRoleTenant);

        $manager->persist($user);
        $manager->flush();

        // retrieve a token
        $response = $client->request('POST', '/v1/authentication/token', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => [
                'email' => 'test@example.com',
                'password' => '$3CR3T',
            ],
        ]);

        $json = $response->toArray();
        $this->assertResponseIsSuccessful();
        $this->assertArrayHasKey('token', $json);

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
        $client->request('GET', '/v1/slides', ['auth_bearer' => $json['token']]);
        $this->assertResponseIsSuccessful();

        // test authorized without tenant (default to first tenant in users tenant list)
        $client->request('GET', '/v1/slides', [
            'auth_bearer' => $json['token'],
            'headers' => [
                TenantScopedAuthenticator::AUTH_TENANT_ID_HEADER => 'ABC',
            ],
        ]);
        $this->assertResponseIsSuccessful();
    }
}
