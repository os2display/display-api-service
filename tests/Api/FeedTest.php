<?php

namespace App\Tests\Api;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Feed;
use App\Entity\FeedSource;
use App\Entity\Slide;
use App\Tests\BaseTestTrait;

class FeedTest extends ApiTestCase
{
    use BaseTestTrait;

    public function testGetCollection(): void
    {
        $response = static::createClient()->request('GET', '/v1/feeds?itemsPerPage=10', ['headers' => ['Content-Type' => 'application/ld+json']]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/Feed',
            '@id' => '/v1/feeds',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 1,
            'hydra:view' => [
                '@id' => '/v1/feeds?itemsPerPage=10',
                '@type' => 'hydra:PartialCollectionView',
            ],
        ]);

        $this->assertCount(1, $response->toArray()['hydra:member']);
    }

    public function testGetItem(): void
    {
        $client = static::createClient();
        $iri = $this->findIriBy(Feed::class, []);

        $client->request('GET', $iri, ['headers' => ['Content-Type' => 'application/ld+json']]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => [
                '@vocab' => 'http://example.com/docs.jsonld#',
                'hydra' => 'http://www.w3.org/ns/hydra/core#',
            ],
            '@type' => 'Feed',
            '@id' => $iri,
        ]);
    }

    public function testCreateUpdateDeleteFeed(): void
    {
        $client = static::createClient();
        $feedSource = $this->findIriBy(FeedSource::class, []);
        $slide = $this->findIriBy(Slide::class, []);

        $response = $client->request('POST', '/v1/feeds', [
            'json' => [
                'title' => 'Test feed',
                'description' => 'This is a test feed',
                'feedSource' => $feedSource,
                'slide' => $slide,
                'configuration' => [
                    'url' => 'https://displayapiservice.local.itkdev.dk',
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
                'title' => 'Feed/title',
                'description' => 'Feed/description',
                'created' => 'Feed/created',
                'modified' => 'Feed/modified',
                'modifiedBy' => 'Feed/modifiedBy',
                'createdBy' => 'Feed/createdBy',
                'feedSource' => 'Feed/feedSource',
                'slide' => 'Feed/slide',
                'configuration' => 'Feed/configuration',
            ],
            '@type' => 'Feed',
            'title' => 'Test feed',
            'description' => 'This is a test feed',
            'feedSource' => $feedSource,
            'slide' => $slide,
            'configuration' => [
                'url' => 'https://displayapiservice.local.itkdev.dk',
            ],
        ]);

        $feedIri = $response->toArray()['@id'];

        $this->assertMatchesRegularExpression('@^/v\d/\w+/([A-Za-z0-9]{26})$@', $feedIri);

        $client->request('PUT', $feedIri, [
            'json' => [
                'title' => 'Updated title',
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@type' => 'Feed',
            '@id' => $feedIri,
            'title' => 'Updated title',
        ]);

        $client->request('DELETE', $feedIri);

        $this->assertResponseStatusCodeSame(204);

        $ulid = $this->iriHelperUtils->getUlidFromIRI($feedIri);
        $this->assertNull(
            static::getContainer()->get('doctrine')->getRepository(Slide::class)->findOneBy(['id' => $ulid])
        );
    }

    public function testCreateInvalidFeed(): void
    {
        static::createClient()->request('POST', '/v1/feeds', [
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
}
