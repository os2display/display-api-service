<?php

namespace App\Tests\Api;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Tenant;
use App\Entity\User;
use App\Entity\UserRoleTenant;
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

        // test not authorized
        $client->request('GET', '/v1/slides');
        $this->assertResponseStatusCodeSame(401);

        // test authorized
        $client->request('GET', '/v1/layouts', ['auth_bearer' => $json['token']]);
        $this->assertResponseIsSuccessful();
    }
}
