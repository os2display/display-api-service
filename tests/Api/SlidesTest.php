<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Entity\Template;
use App\Entity\Tenant\FeedSource;
use App\Entity\Tenant\Slide;
use App\Entity\Tenant\Theme;
use App\Tests\AbstractBaseApiTestCase;

class SlidesTest extends AbstractBaseApiTestCase
{
    public function testGetCollection(): void
    {
        $response = $this->getAuthenticatedClient()->request('GET', '/v2/slides?itemsPerPage=10', ['headers' => ['Content-Type' => 'application/ld+json']]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/Slide',
            '@id' => '/v2/slides',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 61,
            'hydra:view' => [
            '@id' => '/v2/slides?itemsPerPage=10&page=1',
            '@type' => 'hydra:PartialCollectionView',
            'hydra:first' => '/v2/slides?itemsPerPage=10&page=1',
            'hydra:last' => '/v2/slides?itemsPerPage=10&page=7',
            ],
        ]);

        $this->assertCount(10, $response->toArray()['hydra:member']);

        // @TODO: hydra:member[0].templateInfo: Object value found, but an array is required
        //        hydra:member[0].published: Object value found, but an array is required
        // $this->assertMatchesResourceCollectionJsonSchema(Slide::class);
    }

    public function testGetItem(): void
    {
        $client = $this->getAuthenticatedClient();
        $iri = $this->findIriBy(Slide::class, ['tenant' => $this->tenant]);

        $client->request('GET', $iri, ['headers' => ['Content-Type' => 'application/ld+json']]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/Slide',
            '@type' => 'Slide',
            '@id' => $iri,
        ]);
    }

    public function testCreateSlide(): void
    {
        $client = $this->getAuthenticatedClient();
        $templateIri = $this->findIriBy(Template::class, []);
        $themeIri = $this->findIriBy(Theme::class, ['tenant' => $this->tenant]);
        $feedSource = $this->findIriBy(FeedSource::class, ['tenant' => $this->tenant]);

        $response = $client->request('POST', '/v2/slides', [
            'json' => [
                'title' => 'Test slide',
                'description' => 'This is a test slide',
                'templateInfo' => [
                    '@id' => $templateIri,
                    'options' => [
                        'fade' => false,
                    ],
                ],
                'theme' => $themeIri,
                'duration' => 60000,
                'published' => [
                    'from' => '2021-09-21T17:00:01.000Z',
                    'to' => '2021-07-22T17:00:01.000Z',
                ],
                'feed' => [
                    'feedSource' => $feedSource,
                    'configuration' => [
                        'key1' => 'value1',
                    ],
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
            '@context' => '/contexts/Slide',
            '@type' => 'Slide',
            'title' => 'Test slide',
            'description' => 'This is a test slide',
            'modifiedBy' => 'test@example.com',
            'createdBy' => 'test@example.com',
            'templateInfo' => [
                '@id' => $templateIri,
                'options' => [
                    'fade' => false,
                ],
            ],
            'theme' => $themeIri,
            'duration' => 60000,
            'published' => [
                'from' => '2021-09-21T17:00:01.000Z',
                'to' => '2021-07-22T17:00:01.000Z',
            ],
            'content' => [
                'text' => 'Test text',
            ],
            'feed' => [
                'configuration' => [
                  'key1' => 'value1',
                ],
                'feedSource' => $feedSource,
            ],
        ]);
        $this->assertMatchesRegularExpression('@^/v\d/\w+/([A-Za-z0-9]{26})$@', $response->toArray()['@id']);

        // @TODO: templateInfo: Object value found, but an array is required
        //        published: Object value found, but an array is required
        //        content: Object value found, but an array is required
        // $this->assertMatchesResourceItemJsonSchema(Slide::class);
    }

    public function testCreateUnpublishedSlide(): void
    {
        $client = $this->getAuthenticatedClient();
        $templateIri = $this->findIriBy(Template::class, []);
        $themeIri = $this->findIriBy(Theme::class, ['tenant' => $this->tenant]);

        $response = $client->request('POST', '/v2/slides', [
            'json' => [
                'title' => 'Test slide',
                'description' => 'This is a test slide',
                'templateInfo' => [
                    '@id' => $templateIri,
                    'options' => [
                        'fade' => false,
                    ],
                ],
                'theme' => $themeIri,
                'duration' => 60000,
                'published' => [
                    'from' => null,
                    'to' => null,
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
            '@context' => '/contexts/Slide',
            'published' => [
                'from' => null,
                'to' => null,
            ],
        ]);
        $this->assertMatchesRegularExpression('@^/v\d/\w+/([A-Za-z0-9]{26})$@', $response->toArray()['@id']);
    }

    public function testCreateInvalidSlide(): void
    {
        $this->getAuthenticatedClient()->request('POST', '/v2/slides', [
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

    public function testCreateInvalidSlideTime(): void
    {
        $this->getAuthenticatedClient()->request('POST', '/v2/slides', [
            'json' => [
                'published' => [
                    'from' => '2021-09-20T17:00:01.000Z',
                    'to' => '21121-02-22T17:00:01.000Z',
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
            'hydra:description' => '21121-02-22T17:00:01.000Z is not a valid date format, valid format is simplified extended ISO format, e.g 1970-01-01T01:02:03.000Z',
        ]);
    }

    public function testUpdateSlide(): void
    {
        $client = $this->getAuthenticatedClient();
        $iri = $this->findIriBy(Slide::class, ['tenant' => $this->tenant]);

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

    public function testUpdateSlideToUnpublished(): void
    {
        $client = $this->getAuthenticatedClient();
        $iri = $this->findIriBy(Slide::class, ['tenant' => $this->tenant]);

        $client->request('PUT', $iri, [
            'json' => [
                'title' => 'Updated title',
                'published' => [
                    'from' => null,
                    'to' => null,
                ],
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
            'published' => [
                'from' => null,
                'to' => null,
            ],
        ]);
    }

    public function testDeleteSlide(): void
    {
        $client = $this->getAuthenticatedClient();
        $iri = $this->findIriBy(Template::class, []);

        $response = $client->request('POST', '/v2/slides', [
            'json' => [
                'title' => 'Test slide',
                'description' => 'This is a test slide',
                'templateInfo' => [
                    '@id' => $iri,
                    'options' => [
                        'fade' => false,
                    ],
                ],
                'duration' => 60000,
                'published' => [
                    'from' => '2021-09-21T17:00:01.000Z',
                    'to' => '2021-07-22T17:00:01.000Z',
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
