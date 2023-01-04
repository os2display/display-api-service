<?php

namespace App\Tests\Api;

use App\Entity\Template;
use App\Tests\AbstractBaseApiTestCase;

class TemplatesTest extends AbstractBaseApiTestCase
{
    public function testGetCollection(): void
    {
        $response = $this->getAuthenticatedClient()->request('GET', '/v1/templates?itemsPerPage=5', ['headers' => ['Content-Type' => 'application/ld+json']]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/Template',
            '@id' => '/v1/templates',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 1,
            'hydra:view' => [
                '@id' => '/v1/templates?itemsPerPage=5',
                '@type' => 'hydra:PartialCollectionView',
            ],
        ]);

        $this->assertCount(1, $response->toArray()['hydra:member']);

        // @TODO: resources: Object value found, but an array is required. In JSON it's an object but in the entity
        //        it's an key array? So this test will fail.
//        $this->assertMatchesResourceCollectionJsonSchema(Template::class);
    }

    public function testGetItem(): void
    {
        $client = $this->getAuthenticatedClient();
        $iri = $this->findIriBy(Template::class, []);

        $client->request('GET', $iri, ['headers' => ['Content-Type' => 'application/ld+json']]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => [
                '@vocab' => 'http://example.com/docs.jsonld#',
                'hydra' => 'http://www.w3.org/ns/hydra/core#',
                'title' => 'Template/title',
                'description' => 'Template/description',
                'created' => 'Template/created',
                'modified' => 'Template/modified',
                'modifiedBy' => 'Template/modifiedBy',
                'createdBy' => 'Template/createdBy',
                'resources' => 'Template/resources',
            ],
            '@type' => 'Template',
            '@id' => $iri,
        ]);

        // @TODO: resources: Object value found, but an array is required. In JSON it's an object but in the entity
        //        it's an key array? So this test will fail.
        // $this->assertMatchesResourceItemJsonSchema(Template::class);
    }
}
