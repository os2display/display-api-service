<?php

namespace App\Tests\Api;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Slide;
use App\Entity\Template;
use App\Tests\BaseTestTrait;

class SlidesTest extends ApiTestCase
{
    use BaseTestTrait;

    public function testGetCollection(): void
    {
        $response = static::createClient()->request('GET', '/v1/slides?itemsPerPage=10', ['headers' => ['Content-Type' => 'application/ld+json']]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/Slide',
            '@id' => '/v1/slides',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 100,
            'hydra:view' => [
                '@id' => '/v1/slides?itemsPerPage=10&page=1',
                '@type' => 'hydra:PartialCollectionView',
                'hydra:first' => '/v1/slides?itemsPerPage=10&page=1',
                'hydra:last' => '/v1/slides?itemsPerPage=10&page=10',
                'hydra:next' => '/v1/slides?itemsPerPage=10&page=2',
            ],
        ]);

        $this->assertCount(10, $response->toArray()['hydra:member']);

        // @TODO: hydra:member[0].templateInfo: Object value found, but an array is required
        //        hydra:member[0].published: Object value found, but an array is required
//        $this->assertMatchesResourceCollectionJsonSchema(Slide::class);
    }

    public function testGetItem(): void
    {
        $client = static::createClient();
        $iri = $this->findIriBy(Slide::class, []);

        $client->request('GET', $iri, ['headers' => ['Content-Type' => 'application/ld+json']]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => [
                '@vocab' => 'http://example.com/docs.jsonld#',
                'hydra' => 'http://www.w3.org/ns/hydra/core#',
                'title' => 'Slide/title',
                'description' => 'Slide/description',
                'created' => 'Slide/created',
                'modified' => 'Slide/modified',
                'modifiedBy' => 'Slide/modifiedBy',
                'createdBy' => 'Slide/createdBy',
                'templateInfo' => 'Slide/templateInfo',
                'onPlaylists' => 'Slide/onPlaylists',
                'duration' => 'Slide/duration',
                'published' => 'Slide/published',
                'content' => 'Slide/content',
            ],
            '@type' => 'Slide',
            '@id' => $iri,
        ]);
    }

    public function testCreateSlide(): void
    {
        $client = static::createClient();
        $iri = $this->findIriBy(Template::class, []);

        $response = $client->request('POST', '/v1/slides', [
            'json' => [
                'title' => 'Test slide',
                'description' => 'This is a test slide',
                'modifiedBy' => 'Test Tester',
                'createdBy' => 'Hans Tester',
                'templateInfo' => [
                    '@id' => $iri,
                    'options' => [
                        'fade' => false,
                    ],
                ],
                'duration' => 60000,
                'published' => [
                    'from' => '2021-09-21T17:00:01Z',
                    'to' => '2021-07-22T17:00:01Z',
                ],
                'content' => [
                    'text' => 'Test text',
                ],
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => [
                '@vocab' => 'http://example.com/docs.jsonld#',
                'hydra' => 'http://www.w3.org/ns/hydra/core#',
                'title' => 'Slide/title',
                'description' => 'Slide/description',
                'created' => 'Slide/created',
                'modified' => 'Slide/modified',
                'modifiedBy' => 'Slide/modifiedBy',
                'createdBy' => 'Slide/createdBy',
                'templateInfo' => 'Slide/templateInfo',
                'onPlaylists' => 'Slide/onPlaylists',
                'duration' => 'Slide/duration',
                'published' => 'Slide/published',
                'content' => 'Slide/content',
            ],
            '@type' => 'Slide',
            'title' => 'Test slide',
            'description' => 'This is a test slide',
            'modifiedBy' => 'Test Tester',
            'createdBy' => 'Hans Tester',
            'templateInfo' => [
                '@id' => $iri,
                'options' => [
                    'fade' => false,
                ],
            ],
            'duration' => 60000,
            'published' => [
                'from' => '2021-09-21T17:00:01Z',
                'to' => '2021-07-22T17:00:01Z',
            ],
            'content' => [
                'text' => 'Test text',
            ],
        ]);
        $this->assertMatchesRegularExpression('@^/v\d/\w+/([A-Za-z0-9]{26})$@', $response->toArray()['@id']);

        // @TODO: templateInfo: Object value found, but an array is required
        //        published: Object value found, but an array is required
        //        content: Object value found, but an array is required
//        $this->assertMatchesResourceItemJsonSchema(Slide::class);
    }

    public function testCreateInvalidSlide(): void
    {
        static::createClient()->request('POST', '/v1/slides', [
            'json' => [
                'title' => 123456789,
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $this->assertJsonContains([
            '@context' => '/contexts/Error',
            '@type' => 'hydra:Error',
            'hydra:title' => 'An error occurred',
            'hydra:description' => 'The input data is misformatted.',
        ]);
    }

    public function testCreateInvalidSlideTime(): void
    {
        static::createClient()->request('POST', '/v1/slides', [
            'json' => [
                'published' => [
                    'from' => '2021-09-20T17:00:701Z',
                    'to' => '021-02-22T17:00:01Z',
                ],
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $this->assertJsonContains([
            '@context' => '/contexts/Error',
            '@type' => 'hydra:Error',
            'hydra:title' => 'An error occurred',
            'hydra:description' => 'Date format not valid',
        ]);
    }

    public function testUpdateSlide(): void
    {
        $client = static::createClient();
        $iri = $this->findIriBy(Slide::class, []);

        $client->request('PUT', $iri, [
            'json' => [
                'title' => 'Updated title',
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@type' => 'Slide',
            '@id' => $iri,
            'title' => 'Updated title',
        ]);
    }

    public function testDeleteSlide(): void
    {
        $client = static::createClient();
        $iri = $this->findIriBy(Template::class, []);

        $response = $client->request('POST', '/v1/slides', [
            'json' => [
                'title' => 'Test slide',
                'description' => 'This is a test slide',
                'modifiedBy' => 'Test Tester',
                'createdBy' => 'Hans Tester',
                'templateInfo' => [
                    '@id' => $iri,
                    'options' => [
                        'fade' => false,
                    ],
                ],
                'duration' => 60000,
                'published' => [
                    'from' => '2021-09-21T17:00:01Z',
                    'to' => '2021-07-22T17:00:01Z',
                ],
                'content' => [
                    'text' => 'Test text',
                ],
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);

        $iri = $response->toArray()['@id'];
        $client->request('DELETE', $iri);

        $this->assertResponseStatusCodeSame(204);

        $ulid = $this->iriHelperUtils->getUlidFromIRI($iri);
        $this->assertNull(
            static::getContainer()->get('doctrine')->getRepository(Slide::class)->findOneBy(['id' => $ulid])
        );
    }
}
