<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Entity\Tenant\Playlist;
use App\Tests\AbstractBaseApiTestCase;
use Symfony\Component\HttpFoundation\Response;

class PlaylistsTest extends AbstractBaseApiTestCase
{
    public function testGetCollection(): void
    {
        $response = $this->getAuthenticatedClient()->request('GET', '/v2/playlists?itemsPerPage=5', ['headers' => ['Content-Type' => 'application/ld+json']]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/Playlist',
            '@id' => '/v2/playlists',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 11,
            'hydra:view' => [
                '@id' => '/v2/playlists?itemsPerPage=5&page=1',
                '@type' => 'hydra:PartialCollectionView',
                'hydra:first' => '/v2/playlists?itemsPerPage=5&page=1',
                'hydra:last' => '/v2/playlists?itemsPerPage=5&page=3',
                'hydra:next' => '/v2/playlists?itemsPerPage=5&page=2',
            ],
        ]);

        $this->assertCount(5, $response->toArray()['hydra:member']);
    }

    public function testGetCampaigns(): void
    {
        $this->getAuthenticatedClient()->request('GET', '/v2/playlists?itemsPerPage=5&isCampaign=true', ['headers' => ['Content-Type' => 'application/ld+json']]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/Playlist',
            '@id' => '/v2/playlists',
            '@type' => 'hydra:Collection',
          ]);
    }

    public function testGetItem(): void
    {
        $client = $this->getAuthenticatedClient();
        $iri = $this->findIriBy(Playlist::class, ['tenant' => $this->tenant]);

        $client->request('GET', $iri, ['headers' => ['Content-Type' => 'application/ld+json']]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => [
                '@vocab' => 'http://localhost/docs.jsonld#',
                'hydra' => 'http://www.w3.org/ns/hydra/core#',
                'title' => 'Playlist/title',
                'description' => 'Playlist/description',
                'schedules' => 'Playlist/schedules',
                'created' => 'Playlist/created',
                'modified' => 'Playlist/modified',
                'modifiedBy' => 'Playlist/modifiedBy',
                'createdBy' => 'Playlist/createdBy',
                'slides' => 'Playlist/slides',
            ],
            '@type' => 'Playlist',
            '@id' => $iri,
        ]);
    }

    public function testCreatePlaylist(): void
    {
        $response = $this->getAuthenticatedClient()->request('POST', '/v2/playlists', [
            'json' => [
                'title' => 'Test playlist',
                'description' => 'This is a test playlist',
                'schedules' => [
                    [
                        'rrule' => 'DTSTART:20211102T232610Z\nRRULE:FREQ=MINUTELY;COUNT=11;INTERVAL=8',
                        'duration' => 1000,
                    ],
                    [
                        'rrule' => 'DTSTART:20211102T232610Z\nRRULE:FREQ=MINUTELY;COUNT=11;INTERVAL=8',
                        'duration' => 2000,
                    ],
                ],
                'published' => [
                    'from' => '2021-09-21T17:00:01.000Z',
                    'to' => '2021-07-22T17:00:01.000Z',
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
                '@vocab' => 'http://localhost/docs.jsonld#',
                'hydra' => 'http://www.w3.org/ns/hydra/core#',
                'title' => 'Playlist/title',
                'description' => 'Playlist/description',
                'schedules' => 'Playlist/schedules',
                'created' => 'Playlist/created',
                'modified' => 'Playlist/modified',
                'modifiedBy' => 'Playlist/modifiedBy',
                'createdBy' => 'Playlist/createdBy',
                'slides' => 'Playlist/slides',
            ],
            '@type' => 'Playlist',
            'title' => 'Test playlist',
            'description' => 'This is a test playlist',
            'schedules' => [
                [
                    'rrule' => 'DTSTART:20211102T232610Z\nRRULE:FREQ=MINUTELY;COUNT=11;INTERVAL=8',
                    'duration' => 1000,
                ],
                [
                    'rrule' => 'DTSTART:20211102T232610Z\nRRULE:FREQ=MINUTELY;COUNT=11;INTERVAL=8',
                    'duration' => 2000,
                ],
            ],
            'modifiedBy' => 'test@example.com',
            'createdBy' => 'test@example.com',
            'published' => [
                'from' => '2021-09-21T17:00:01.000Z',
                'to' => '2021-07-22T17:00:01.000Z',
            ],
        ]);
        $this->assertMatchesRegularExpression('@^/v\d/\w+/([A-Za-z0-9]{26})$@', $response->toArray()['@id']);

        $response = $this->getAuthenticatedClient()->request('POST', '/v2/playlists', [
            'json' => [
                'title' => 'Test playlist',
                'description' => 'This is a test playlist',
                'schedules' => [
                    [
                        'rrule' => 'DTSTART:20211102T232610Z\nRRULE:FREQ=MINUTELY;COUNT=11;INTERVAL=8',
                        'duration' => 1000,
                    ],
                    [
                        'rrule' => 'DTSTART:20211102T232610Z\nRRULE:FREQ=MINUTELY;COUNT=11;INTERVAL=8',
                        'duration' => 2000,
                    ],
                ],
                'published' => [
                    'from' => '2021-09-21T17:00:01.000Z',
                    'to' => '2021-07-22T17:00:01.000Z',
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
                '@vocab' => 'http://localhost/docs.jsonld#',
                'hydra' => 'http://www.w3.org/ns/hydra/core#',
                'title' => 'Playlist/title',
                'description' => 'Playlist/description',
                'schedules' => 'Playlist/schedules',
                'created' => 'Playlist/created',
                'modified' => 'Playlist/modified',
                'modifiedBy' => 'Playlist/modifiedBy',
                'createdBy' => 'Playlist/createdBy',
                'slides' => 'Playlist/slides',
            ],
            '@type' => 'Playlist',
            'title' => 'Test playlist',
            'description' => 'This is a test playlist',
            'schedules' => [
                [
                    'rrule' => 'DTSTART:20211102T232610Z\nRRULE:FREQ=MINUTELY;COUNT=11;INTERVAL=8',
                    'duration' => 1000,
                ],
                [
                    'rrule' => 'DTSTART:20211102T232610Z\nRRULE:FREQ=MINUTELY;COUNT=11;INTERVAL=8',
                    'duration' => 2000,
                ],
            ],
            'modifiedBy' => 'test@example.com',
            'createdBy' => 'test@example.com',
            'published' => [
                'from' => '2021-09-21T17:00:01.000Z',
                'to' => '2021-07-22T17:00:01.000Z',
            ],
        ]);
        $this->assertMatchesRegularExpression('@^/v\d/\w+/([A-Za-z0-9]{26})$@', $response->toArray()['@id']);

        // Test rrule on created playlist
        $this->getAuthenticatedClient()->request('GET', $response->toArray()['@id']);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            'schedules' => [
                [
                    'rrule' => 'DTSTART:20211102T232610Z\nRRULE:FREQ=MINUTELY;COUNT=11;INTERVAL=8',
                    'duration' => 1000,
                ],
                [
                    'rrule' => 'DTSTART:20211102T232610Z\nRRULE:FREQ=MINUTELY;COUNT=11;INTERVAL=8',
                    'duration' => 2000,
                ],
            ],
        ]);

        // @TODO: published: Object value found, but an array is required
        // $this->assertMatchesResourceItemJsonSchema(Playlist::class);
    }

    public function testCreateUnpublishedPlaylist(): void
    {
        $this->getAuthenticatedClient()->request('POST', '/v2/playlists', [
            'json' => [
                'title' => 'Test playlist',
                'description' => 'This is a test playlist',
                'schedules' => [
                    [
                        'rrule' => 'DTSTART:20211102T232610Z\nRRULE:FREQ=MINUTELY;COUNT=11;INTERVAL=8',
                        'duration' => 1000,
                    ],
                    [
                        'rrule' => 'DTSTART:20211102T232610Z\nRRULE:FREQ=MINUTELY;COUNT=11;INTERVAL=8',
                        'duration' => 2000,
                    ],
                ],
                'published' => [
                    'from' => null,
                    'to' => null,
                ],
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@type' => 'Playlist',
            'title' => 'Test playlist',
            'description' => 'This is a test playlist',
            'published' => [
                'from' => null,
                'to' => null,
            ],
        ]);
    }

    public function testCreateInvalidPlaylist(): void
    {
        $this->getAuthenticatedClient()->request('POST', '/v2/playlists', [
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

    public function testCreateInvalidPlaylistTime(): void
    {
        $this->getAuthenticatedClient()->request('POST', '/v2/playlists', [
            'json' => [
                'published' => [
                    'from' => '2021-09-201T17:00:01.000Z',
                    'to' => '2021-42-22T17:00:01.000Z',
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
            'hydra:description' => '2021-09-201T17:00:01.000Z is not a valid date format, valid format is simplified extended ISO format, e.g 1970-01-01T01:02:03.000Z',
        ]);
    }

    public function testUpdatePlaylist(): void
    {
        $client = $this->getAuthenticatedClient();
        $iri = $this->findIriBy(Playlist::class, ['tenant' => $this->tenant]);

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
            '@type' => 'Playlist',
            '@id' => $iri,
            'title' => 'Updated title',
        ]);
    }

    public function testUpdatePlaylistToUnpublished(): void
    {
        $client = $this->getAuthenticatedClient();
        $iri = $this->findIriBy(Playlist::class, ['tenant' => $this->tenant]);

        $response = $client->request('PUT', $iri, [
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
            '@type' => 'Playlist',
            '@id' => $iri,
            'title' => 'Updated title',
            'published' => [
                'from' => null,
                'to' => null,
            ],
        ]);
    }

    public function testDeletePlaylist(): void
    {
        $client = $this->getAuthenticatedClient();
        $iri = $this->findIriBy(Playlist::class, ['tenant' => $this->tenant]);

        $client->request('DELETE', $iri);

        $this->assertResponseStatusCodeSame(204);

        $ulid = $this->iriHelperUtils->getUlidFromIRI($iri);
        $this->assertNull(
            static::getContainer()->get('doctrine')->getRepository(Playlist::class)->findOneBy(['id' => $ulid])
        );
    }

    public function testGetScreensList(): void
    {
        $client = $this->getAuthenticatedClient('ROLE_ADMIN');
        $iri = $this->findIriBy(Playlist::class, ['tenant' => $this->tenant]);
        $ulid = $this->iriHelperUtils->getUlidFromIRI($iri);

        $client->request('GET', '/v2/campaigns/'.$ulid.'/screens', ['headers' => ['Content-Type' => 'application/ld+json']]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ScreenCampaign',
            '@id' => '/v2/campaigns/'.$ulid.'/screens',
            '@type' => 'hydra:Collection',
        ]);
    }

    public function testSharedPlaylists(): void
    {
        $response = $this->getAuthenticatedClient()->request('GET', '/v2/playlists?itemsPerPage=20', ['headers' => ['Content-Type' => 'application/ld+json']]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/Playlist',
            '@id' => '/v2/playlists',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 13,
        ]);

        $this->assertCount(13, $response->toArray()['hydra:member']);
    }
}
