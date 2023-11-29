<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Tests\AbstractBaseApiTestCase;

class GetTenantTest extends AbstractBaseApiTestCase
{
    public function testGetCollection(): void
    {
        $response = $this->getAuthenticatedClient()->request('GET', '/v1/tenants?itemsPerPage=10', ['headers' => ['Content-Type' => 'application/ld+json']]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/Tenant',
            '@id' => '/v1/tenants',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 3,
        ]);

        $this->assertCount(3, $response->toArray()['hydra:member']);
    }
}
