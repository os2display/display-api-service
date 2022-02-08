<?php

namespace App\Tests\Api;

use App\Entity\Tenant\ScreenLayout;
use App\Tests\AbstractBaseApiTestCase;

class LayoutsTest extends AbstractBaseApiTestCase
{
    public function testGetLayoutCollection(): void
    {
        $client = $this->getAuthenticatedClient();

        $response = $client->request('GET', '/v1/layouts?itemsPerPage=2', ['headers' => ['Content-Type' => 'application/ld+json']]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ScreenLayout',
            '@id' => '/v1/layouts',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 10,
            'hydra:view' => [
                '@id' => '/v1/layouts?itemsPerPage=2&page=1',
                '@type' => 'hydra:PartialCollectionView',
                'hydra:first' => '/v1/layouts?itemsPerPage=2&page=1',
                'hydra:last' => '/v1/layouts?itemsPerPage=2&page=5',
                'hydra:next' => '/v1/layouts?itemsPerPage=2&page=2',
            ],
        ]);

        $this->assertCount(2, $response->toArray()['hydra:member']);

        // @TODO: We should have a test here matching the json schema for ScreenLayout, but it's not possible as it
        //        contains a sub-resource ScreenLayoutRegions. Figure out if matching the keys in the array is possible
        //        to validate data structure.
//        $this->assertMatchesResourceCollectionJsonSchema(ScreenLayout::class);
    }

    public function testGetLayoutItem(): void
    {
        $client = $this->getAuthenticatedClient();
        $iri = $this->findIriBy(ScreenLayout::class, []);

        $client->request('GET', $iri, ['headers' => ['Content-Type' => 'application/ld+json']]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => [
                '@vocab' => 'http://example.com/docs.jsonld#',
                'hydra' => 'http://www.w3.org/ns/hydra/core#',
                'title' => 'ScreenLayout/title',
                'grid' => 'ScreenLayout/grid',
                'regions' => 'ScreenLayout/regions',
            ],
            '@type' => 'ScreenLayout',
            '@id' => $iri,
        ]);
    }
}
