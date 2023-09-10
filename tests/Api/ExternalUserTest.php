<?php

namespace App\Tests\Api;

use App\Entity\Tenant\Playlist;
use App\Tests\AbstractBaseApiTestCase;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class ExternalUserTest extends AbstractBaseApiTestCase
{
    public function testFlow(): void
    {
        $authenticatedClient = $this->getAuthenticatedClient('ROLE_EXTERNAL_USER_ADMIN');

        $response1 = $authenticatedClient->request(
            'POST',
            '/v1/external-user-activation-codes',
            [
                'body' => json_encode(['displayName'=> 'Test Testesen', 'roles' => ['ROLE_EXTERNAL_USER_ADMIN']]),
                'headers' => ['Content-Type' => 'application/ld+json'],
            ]
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertEquals(12, strlen($response1->toArray()['code']));

        $response2 = $authenticatedClient->request(
            'POST',
            '/v1/external-user-activation-codes',
            [
                'body' => json_encode(['displayName'=> 'Test Testesen', 'roles' => ['ROLE_EXTERNAL_USER_ADMIN']]),
                'headers' => ['Content-Type' => 'application/ld+json'],
            ]
        );

        $this->assertEquals(12, strlen($response2->toArray()['code']));

        $response3 = $authenticatedClient->request(
            'GET',
            '/v1/external-user-activation-codes',
            [
                'headers' => ['Content-Type' => 'application/ld+json'],
            ]
        );

        $this->assertJsonContains([
            '@context' => '/contexts/ExternalUserActivationCode',
            '@id' => '/v1/external-user-activation-codes',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 2,
        ]);

        $this->assertCount(2, $response3->toArray()['hydra:member']);
    }
}
