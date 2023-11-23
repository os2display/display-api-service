<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Entity\Tenant\FeedSource;
use App\Tests\AbstractBaseApiTestCase;

class FeedSourceTest extends AbstractBaseApiTestCase
{
    public function testGetCollection(): void
    {
        $client = $this->getAuthenticatedClient();
        $response = $client->request('GET', '/v1/feed-sources?itemsPerPage=10', ['headers' => ['Content-Type' => 'application/ld+json']]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/FeedSource',
            '@id' => '/v1/feed-sources',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 1,
            'hydra:view' => [
                '@id' => '/v1/feed-sources?itemsPerPage=10',
                '@type' => 'hydra:PartialCollectionView',
            ],
        ]);

        $this->assertCount(1, $response->toArray()['hydra:member']);
    }

    public function testGetItem(): void
    {
        $client = $this->getAuthenticatedClient();
        $iri = $this->findIriBy(FeedSource::class, ['tenant' => $this->tenant]);

        $client->request('GET', $iri, ['headers' => ['Content-Type' => 'application/ld+json']]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => [
                '@vocab' => 'http://localhost/docs.jsonld#',
                'hydra' => 'http://www.w3.org/ns/hydra/core#',
            ],
            '@type' => 'FeedSource',
            '@id' => $iri,
        ]);
    }
}
