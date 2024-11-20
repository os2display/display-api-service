<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Entity\Tenant\Feed;
use App\Entity\Tenant\FeedSource;
use App\Entity\Tenant\Slide;
use App\Tests\AbstractBaseApiTestCase;
use Symfony\Component\HttpClient\Exception\ClientException;

class FeedSourceTest extends AbstractBaseApiTestCase
{
    public function testGetCollection(): void
    {
        $client = $this->getAuthenticatedClient();
        $response = $client->request('GET', '/v2/feed-sources?itemsPerPage=10', ['headers' => ['Content-Type' => 'application/ld+json']]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/FeedSource',
            '@id' => '/v2/feed-sources',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 2,
            'hydra:view' => [
                '@id' => '/v2/feed-sources?itemsPerPage=10',
                '@type' => 'hydra:PartialCollectionView',
            ],
        ]);

        $this->assertCount(2, $response->toArray()['hydra:member']);
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

    public function testCreateFeedSource(): void
    {
        $client = $this->getAuthenticatedClient('ROLE_ADMIN');

        $response = $client->request('POST', '/v2/feed-sources', [
            'json' => [
                'title' => 'Test feed source',
                'description' => 'This is a test feed source',
                'feedType' => \App\Feed\EventDatabaseApiFeedType::class,
                'secrets' => [
                    'host' => 'https://www.test.dk',
                ],
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/FeedSource',
            '@type' => 'FeedSource',
            'title' => 'Test feed source',
            'description' => 'This is a test feed source',
            'feedType' => \App\Feed\EventDatabaseApiFeedType::class,
            'secrets' => [
                'host' => 'https://www.test.dk',
            ],
            'createdBy' => 'test@example.com',
            'modifiedBy' => 'test@example.com',
        ]);
        $this->assertMatchesRegularExpression('@^/v\d/[\w-]+/([A-Za-z0-9]{26})$@', $response->toArray()['@id']);
    }

    public function testCreateFeedSourceWithoutTitle(): void
    {
        $client = $this->getAuthenticatedClient('ROLE_ADMIN');

        $this->expectException(ClientException::class);

        $response = $client->request('POST', '/v2/feed-sources', [
            'json' => [
                'description' => 'This is a test feed source',
                'outputType' => 'This is a test output type',
                'feedType' => 'This is a test feed type',
                'secrets' => [
                    'test secret',
                ],

                'supportedFeedOutputType' => 'Supported feed output type',
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);
        $this->assertMatchesRegularExpression('@^/v\d/[\w-]+/([A-Za-z0-9]{26})$@', $response->toArray()['@id']);
    }

    public function testCreateFeedSourceWithoutDescription(): void
    {
        $client = $this->getAuthenticatedClient('ROLE_ADMIN');

        $this->expectException(ClientException::class);

        $response = $client->request('POST', '/v2/feed-sources', [
            'json' => [
                'title' => 'Test feed source',
                'outputType' => 'This is a test output type',
                'feedType' => 'This is a test feed type',
                'secrets' => [
                    'test secret',
                ],

                'supportedFeedOutputType' => 'Supported feed output type',
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);

        $this->assertMatchesRegularExpression('@^/v\d/[\w-]+/([A-Za-z0-9]{26})$@', $response->toArray()['@id']);
    }

    public function testCreateFeedSourceWithEventDatabaseFeedTypeWithoutRequiredSecret(): void
    {
        $client = $this->getAuthenticatedClient('ROLE_ADMIN');

        $this->expectException(ClientException::class);

        $response = $client->request('POST', '/v2/feed-sources', [
            'json' => [
                'title' => 'Test feed source',
                'outputType' => 'This is a test output type',
                'feedType' => \App\Feed\EventDatabaseApiFeedType::class,
                'secrets' => [
                    'test secret',
                ],

                'supportedFeedOutputType' => 'Supported feed output type',
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);

        $this->assertMatchesRegularExpression('@^/v\d/[\w-]+/([A-Za-z0-9]{26})$@', $response->toArray()['@id']);
    }

    public function testUpdateFeedSource(): void
    {
        $client = $this->getAuthenticatedClient('ROLE_ADMIN');
        $iri = $this->findIriBy(FeedSource::class, ['tenant' => $this->tenant, 'title' => 'Test feed source']);

        $client->request('PUT', $iri, [
            'json' => [
                'title' => 'Updated title',
                'description' => 'Updated description',
                'outputType' => 'This is a test output type',
                'feedType' => \App\Feed\EventDatabaseApiFeedType::class,
                'secrets' => [
                ],
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@type' => 'FeedSource',
            '@id' => $iri,
            'title' => 'Updated title',
            'description' => 'Updated description',
        ]);
    }

    public function testDeleteFeedSource(): void
    {
        $client = $this->getAuthenticatedClient('ROLE_ADMIN');

        $response = $client->request('POST', '/v2/feed-sources', [
            'json' => [
                'title' => 'Test feed source',
                'description' => 'This is a test feed source',
                'outputType' => 'This is a test output type',
                'feedType' => \App\Feed\EventDatabaseApiFeedType::class,
                'secrets' => [
                    'host' => 'https://www.test.dk',
                ],
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);
        $this->assertResponseIsSuccessful();

        $iri = $response->toArray()['@id'];
        $client->request('DELETE', $iri);

        $this->assertResponseStatusCodeSame(204);

        $ulid = $this->iriHelperUtils->getUlidFromIRI($iri);
        $this->assertNull(
            static::getContainer()->get('doctrine')->getRepository(FeedSource::class)->findOneBy(['id' => $ulid])
        );
    }

    public function testDeleteFeedSourceInUse(): void
    {
        $client = $this->getAuthenticatedClient('ROLE_ADMIN');

        $manager = static::getContainer()->get('doctrine')->getManager();

        $slide = $manager->getRepository(Slide::class)->findOneBy(['title' => 'slide_abc_1']);
        $this->assertInstanceOf(Slide::class, $slide, 'This test requires the slide titled slide_abc_1 with connected feed and feed source');

        $feed = $slide->getFeed();
        $this->assertInstanceOf(Feed::class, $feed, 'This test requires a slide with a feed connected');

        $feedSource = $feed->getFeedSource();
        $this->assertInstanceOf(FeedSource::class, $feedSource, 'This test requires a feed with a feed source connected');

        $feedSourceIri = $this->findIriBy(FeedSource::class, ['id' => $feedSource->getId()]);
        $client->request('DELETE', $feedSourceIri);

        // Assert that delete request throws an integrity constraint violation error
        $this->assertResponseStatusCodeSame(409);

        $ulid = $this->iriHelperUtils->getUlidFromIRI($feedSourceIri);

        $this->assertNotNull(
            $manager->getRepository(FeedSource::class)->findOneBy(['id' => $ulid])
        );
    }
}
