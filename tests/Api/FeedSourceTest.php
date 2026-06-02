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
                'feedType' => \App\Feed\EventDatabaseApiV2FeedType::class,
                'secrets' => [
                    'host' => 'https://www.test.dk',
                    'apikey' => 'test-api-key',
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
            'feedType' => \App\Feed\EventDatabaseApiV2FeedType::class,
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
                'feedType' => \App\Feed\EventDatabaseApiV2FeedType::class,
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
                'feedType' => \App\Feed\EventDatabaseApiV2FeedType::class,
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
                'feedType' => \App\Feed\EventDatabaseApiV2FeedType::class,
                'secrets' => [
                    'host' => 'https://www.test.dk',
                    'apikey' => 'test-api-key',
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

    /**
     * A feed source whose stored feed type was removed (e.g. a deprecated type
     * dropped in 3.0.0) must still be readable. The provider degrades gracefully
     * instead of throwing UnknownFeedTypeException and returning HTTP 500, so the
     * row stays listable and can be cleaned up.
     */
    public function testGetItemWithRemovedFeedTypeDoesNotError(): void
    {
        $client = $this->getAuthenticatedClient('ROLE_ADMIN');

        $manager = static::getContainer()->get('doctrine')->getManager();
        $tenant = $manager->getRepository(\App\Entity\Tenant::class)->findOneBy(['tenantKey' => $this->tenant->getTenantKey()]);

        $feedSource = new FeedSource();
        $feedSource->setTitle('Feed source with removed feed type');
        $feedSource->setDescription('References a feed type that no longer exists');
        $feedSource->setFeedType('App\\Feed\\KobaFeedType');
        $feedSource->setSupportedFeedOutputType('calendar');
        $feedSource->setSecrets(['kobaApiKey' => 'secret-value']);
        $feedSource->setTenant($tenant);
        $manager->persist($feedSource);
        $manager->flush();

        $iri = $this->findIriBy(FeedSource::class, ['id' => $feedSource->getId()]);
        $response = $client->request('GET', $iri, ['headers' => ['Content-Type' => 'application/ld+json']]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@type' => 'FeedSource',
            'feedType' => 'App\\Feed\\KobaFeedType',
        ]);

        // No secrets are exposed for an unresolvable feed type.
        $this->assertSame([], $response->toArray()['secrets']);
    }

    /**
     * Writing a feed source with a feed type that cannot be resolved is rejected
     * with 422 (mapped from UnknownFeedTypeException), not a 500. This is the
     * "reject writes" half of the policy.
     */
    public function testCreateFeedSourceWithUnknownFeedTypeIsRejected(): void
    {
        $client = $this->getAuthenticatedClient('ROLE_ADMIN');

        $client->request('POST', '/v2/feed-sources', [
            'json' => [
                'title' => 'Feed source with unknown feed type',
                'description' => 'References a feed type that no longer exists',
                'feedType' => 'App\\Feed\\KobaFeedType',
                'secrets' => [],
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    /**
     * Reading a Feed whose feed source references a removed feed type must also
     * degrade (no 500). FeedProvider embeds the feed source via
     * FeedSourceProvider::toOutput, which catches the unknown type.
     */
    public function testGetFeedWithRemovedFeedTypeDoesNotError(): void
    {
        $client = $this->getAuthenticatedClient('ROLE_ADMIN');

        $manager = static::getContainer()->get('doctrine')->getManager();
        $tenant = $manager->getRepository(\App\Entity\Tenant::class)->findOneBy(['tenantKey' => $this->tenant->getTenantKey()]);
        $template = $manager->getRepository(\App\Entity\Template::class)->findOneBy([]);

        $feedSource = new FeedSource();
        $feedSource->setTitle('Feed source with removed feed type (feed read)');
        $feedSource->setDescription('References a feed type that no longer exists');
        $feedSource->setFeedType('App\\Feed\\KobaFeedType');
        $feedSource->setSupportedFeedOutputType('calendar');
        $feedSource->setSecrets(['kobaApiKey' => 'secret-value']);
        $feedSource->setTenant($tenant);
        $manager->persist($feedSource);

        $slide = new Slide();
        $slide->setTitle('Slide for removed-feed-type feed');
        $slide->setTemplate($template);
        $slide->setPublishedFrom(null);
        $slide->setPublishedTo(null);
        $slide->setTenant($tenant);
        $manager->persist($slide);

        $feed = new Feed();
        $feed->setFeedSource($feedSource);
        $feed->setTenant($tenant);
        $manager->persist($feed);
        $slide->setFeed($feed);

        $manager->flush();

        $iri = $this->findIriBy(Feed::class, ['id' => $feed->getId()]);
        $client->request('GET', $iri, ['headers' => ['Content-Type' => 'application/ld+json']]);

        // The feed read embeds the feed source via FeedSourceProvider::toOutput;
        // an unresolvable feed type must degrade there, so the read returns 200
        // (the embedded feed source is referenced as an IRI in the Feed payload).
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['@type' => 'Feed']);
    }
}
