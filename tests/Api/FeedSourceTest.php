<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Entity\Tenant\Feed;
use App\Entity\Tenant\FeedSource;
use App\Tests\AbstractBaseApiTestCase;

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
                'outputType' => 'This is a test output type',
                'feedType' => 'This is a test feed type',
                'secrets' => [
                    'test secret',
                ],
                'feeds' => [
                    'test feed',
                ],
                'supportedFeedOutputType' => 'Supported feed output type',
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
            'feedType' => 'This is a test feed type',
            'secrets' => [
                'test secret',
            ],
            'feeds' => [],
            'supportedFeedOutputType' => 'Supported feed output type',
            'title' => 'Test feed source',
            'description' => 'This is a test feed source',
            'createdBy' => 'test@example.com',
            'modifiedBy' => 'test@example.com',
        ]);
        $this->assertMatchesRegularExpression('@^/v\d/[\w-]+/([A-Za-z0-9]{26})$@', $response->toArray()['@id']);
    }

    public function testCreateInvalidFeedSource(): void
    {
        $this->getAuthenticatedClient('ROLE_ADMIN')->request('POST', '/v2/feed-sources', [
            'json' => [
                'title' => 123_456_789,
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

    public function testUpdateFeedSource(): void
    {
        $client = $this->getAuthenticatedClient('ROLE_ADMIN');
        $iri = $this->findIriBy(FeedSource::class, ['tenant' => $this->tenant, 'title' => 'feed_source_abc_1']);

        $client->request('PUT', $iri, [
            'json' => [
                'title' => 'Updated title',
                'feedType' => 'Updated feed type',
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
            'feedType' => 'Updated feed type',
        ]);
    }

    public function testDeleteFeedSource(): void
    {
        $client = $this->getAuthenticatedClient('ROLE_ADMIN');

        $response = $client->request('POST', '/v2/feed-sources', [
            'json' => [
                'title' => 'Test feed source',
                'description' => 'This is a test feed source',
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

        $qb = static::getContainer()->get('doctrine')->getRepository(Feed::class)->createQueryBuilder('f');

        /** @var Feed $feed */
        $feed = $qb
            ->leftJoin('f.feedSource', 'feedSource')->addSelect('feedSource')
            ->leftJoin('f.tenant', 'tenant')->addSelect('tenant')
            ->where('f.feedSource IS NOT NULL')
            ->andWhere('tenant.tenantKey = :tenantKey')
            ->setParameter('tenantKey', 'ABC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        $feedSource = $feed->getFeedSource();

        $feedSourceId = $feedSource->getId();

        $feedSourceIri = $this->findIriBy(FeedSource::class, ['id' => $feedSourceId]);

        $client->request('DELETE', $feedSourceIri);

        $this->assertResponseStatusCodeSame(500);

        $ulid = $this->iriHelperUtils->getUlidFromIRI($feedSourceIri);

        $this->assertNotNull(
            static::getContainer()->get('doctrine')->getRepository(FeedSource::class)->findOneBy(['id' => $ulid])
        );
    }
}
