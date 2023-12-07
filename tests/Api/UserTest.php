<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Tests\AbstractBaseApiTestCase;
use App\Utils\Roles;

class UserTest extends AbstractBaseApiTestCase
{
    public function testExternalUserFlow(): void
    {
        $authenticatedClient = $this->getAuthenticatedClient(Roles::ROLE_EXTERNAL_USER_ADMIN);

        // Create two activation codes.

        $response1 = $authenticatedClient->request(
            'POST',
            '/v1/user-activation-codes',
            [
                'body' => json_encode(['displayName' => 'Test Testesen', 'roles' => [Roles::ROLE_EXTERNAL_USER_ADMIN]]),
                'headers' => ['Content-Type' => 'application/ld+json'],
            ]
        );
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertEquals(12, strlen($response1->toArray()['code']));
        $code1 = $response1->toArray()['code'];

        $response2 = $authenticatedClient->request(
            'POST',
            '/v1/user-activation-codes',
            [
                'body' => json_encode(['displayName' => 'Test Testesen 2', 'roles' => [Roles::ROLE_EXTERNAL_USER_ADMIN]]),
                'headers' => ['Content-Type' => 'application/ld+json'],
            ]
        );
        $this->assertEquals(12, strlen($response2->toArray()['code']));

        // Assert that activation codes have been created.
        $response3 = $authenticatedClient->request(
            'GET',
            '/v1/user-activation-codes',
            [
                'headers' => ['Content-Type' => 'application/ld+json'],
            ]
        );
        $this->assertJsonContains([
            '@context' => '/contexts/UserActivationCode',
            '@id' => '/v1/user-activation-codes',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 2,
        ]);
        $this->assertCount(2, $response3->toArray()['hydra:member']);

        $externalClient = $this->getAuthenticatedClientForExternalUser(true);

        // Assert that the user is not in a tenant.
        $this->assertEquals(0, count($this->user->getUserRoleTenants()));

        // Use the activation code.
        $externalClient->request(
            'POST',
            '/v1/user-activation-codes/activate',
            [
                'json' => [
                    'activationCode' => $code1,
                ],
                'headers' => ['Content-Type' => 'application/ld+json'],
            ]
        );
        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(204);

        // Assert that the user has been added to a tenant and has got the display set as full name.
        $this->getAuthenticatedClientForExternalUser();
        $this->assertEquals(1, count($this->user->getUserRoleTenants()));
        $this->assertEquals('Test Testesen', $this->user->getFullName());
        $this->assertEquals('TestTestesen@ext', $this->user->getEmail());

        $userId = $this->user->getId();

        // Assert that activation code has been removed.
        $authenticatedClient = $this->getAuthenticatedClient(Roles::ROLE_EXTERNAL_USER_ADMIN);
        $response4 = $authenticatedClient->request(
            'GET',
            '/v1/user-activation-codes',
            [
                'headers' => ['Content-Type' => 'application/ld+json'],
            ]
        );
        $this->assertJsonContains([
            '@context' => '/contexts/UserActivationCode',
            '@id' => '/v1/user-activation-codes',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 1,
        ]);
        $this->assertCount(1, $response4->toArray()['hydra:member']);

        $response5 = $authenticatedClient->request(
            'POST',
            '/v1/user-activation-codes',
            [
                'body' => json_encode(['displayName' => 'Test Testesen 2', 'roles' => [Roles::ROLE_EXTERNAL_USER_ADMIN]]),
                'headers' => ['Content-Type' => 'application/ld+json'],
            ]
        );
        $this->assertResponseStatusCodeSame(400);

        $authenticatedClient = $this->getAuthenticatedClient(Roles::ROLE_EXTERNAL_USER_ADMIN);
        $resp = $authenticatedClient->request('GET', '/v1/users');
        $this->assertResponseStatusCodeSame(200);
        $this->assertCount(1, $resp->toArray()['hydra:member']);

        // Test remove user from tenant, denied for ROLE_EDITOR.
        $authenticatedClient = $this->getAuthenticatedClient(Roles::ROLE_EDITOR);
        $authenticatedClient->request('DELETE', "/v1/users/$userId/remove-from-tenant");
        $this->assertResponseStatusCodeSame(403);

        // Test remove user from tenant.
        $authenticatedClient = $this->getAuthenticatedClient(Roles::ROLE_EXTERNAL_USER_ADMIN);
        $authenticatedClient->request('DELETE', "/v1/users/$userId/remove-from-tenant");
        $this->assertResponseStatusCodeSame(204);

        $this->getAuthenticatedClientForExternalUser();
        $this->assertCount(0, $this->user->getUserRoleTenants());
    }

    public function testExternalUserInvalidCode(): void
    {
        $externalClient = $this->getAuthenticatedClientForExternalUser(true);

        // Use the activation code.
        $response1 = $externalClient->request(
            'POST',
            '/v1/user-activation-codes/activate',
            [
                'json' => [
                    'activationCode' => 'CODEDOESNOTEXIST',
                ],
                'headers' => ['Content-Type' => 'application/ld+json'],
            ]
        );
        $this->assertResponseStatusCodeSame(404);
    }

    public function testAccess(): void
    {
        $authenticatedClient = $this->getAuthenticatedClient(Roles::ROLE_EDITOR);
        $authenticatedClient->request('GET', '/v1/users');
        $this->assertResponseStatusCodeSame(403);

        $authenticatedClient = $this->getAuthenticatedClient(Roles::ROLE_USER);
        $authenticatedClient->request('GET', '/v1/users');
        $this->assertResponseStatusCodeSame(403);

        $authenticatedClient = $this->getAuthenticatedClient(Roles::ROLE_EXTERNAL_USER);
        $authenticatedClient->request('GET', '/v1/users');
        $this->assertResponseStatusCodeSame(403);

        $authenticatedClient = $this->getAuthenticatedClient(Roles::ROLE_EXTERNAL_USER_ADMIN);
        $resp = $authenticatedClient->request('GET', '/v1/users');
        $this->assertResponseStatusCodeSame(200);
        $this->assertCount(0, $resp->toArray()['hydra:member']);

        $authenticatedClient = $this->getAuthenticatedClient(Roles::ROLE_ADMIN);
        $resp = $authenticatedClient->request('GET', '/v1/users');
        $this->assertResponseStatusCodeSame(200);
        $this->assertCount(0, $resp->toArray()['hydra:member']);

        $authenticatedClient = $this->getAuthenticatedClient(Roles::ROLE_SUPER_ADMIN);
        $resp = $authenticatedClient->request('GET', '/v1/users');
        $this->assertResponseStatusCodeSame(200);
        $this->assertCount(0, $resp->toArray()['hydra:member']);

        $authenticatedClient = $this->getAuthenticatedClient(Roles::ROLE_USER_ADMIN);
        $resp = $authenticatedClient->request('GET', '/v1/users');
        $this->assertResponseStatusCodeSame(200);
        $this->assertCount(3, $resp->toArray()['hydra:member']);

        $toArray = $resp->toArray();
        $userIds = array_map(function ($el) { return $el['@id']; }, $toArray['hydra:member']);

        foreach ($userIds as $userId) {
            $authenticatedClient->request('GET', $userId);
            $this->assertResponseStatusCodeSame(200);
        }

        $authenticatedClient = $this->getAuthenticatedClient(Roles::ROLE_EDITOR);

        foreach ($userIds as $userId) {
            $authenticatedClient->request('GET', $userId);
            $this->assertResponseStatusCodeSame(403);
        }

        $authenticatedClient = $this->getAuthenticatedClient(Roles::ROLE_ADMIN);

        foreach ($userIds as $userId) {
            $authenticatedClient->request('GET', $userId);
            $this->assertResponseStatusCodeSame(404);
        }
    }
}
