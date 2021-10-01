<?php

namespace App\Tests\Api;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Playlist;
use App\Entity\PlaylistSlide;
use App\Entity\Slide;
use App\Tests\BaseTestTrait;

class PlaylistsTest extends ApiTestCase
{
    use BaseTestTrait;

    public function testGetCollection(): void
    {
        $response = static::createClient()->request('GET', '/v1/playlists?itemsPerPage=5', ['headers' => ['Content-Type' => 'application/ld+json']]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/Playlist',
            '@id' => '/v1/playlists',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 10,
            'hydra:view' => [
                '@id' => '/v1/playlists?itemsPerPage=5&page=1',
                '@type' => 'hydra:PartialCollectionView',
                'hydra:first' => '/v1/playlists?itemsPerPage=5&page=1',
                'hydra:last' => '/v1/playlists?itemsPerPage=5&page=2',
                'hydra:next' => '/v1/playlists?itemsPerPage=5&page=2',
            ],
        ]);

        $this->assertCount(5, $response->toArray()['hydra:member']);

        // @TODO: published: Object value found, but an array is required
//        $this->assertMatchesResourceCollectionJsonSchema(Playlist::class, 'get-v1-screen-groups');
    }

    public function testGetItem(): void
    {
        $client = static::createClient();
        $iri = $this->findIriBy(Playlist::class, []);

        $client->request('GET', $iri, ['headers' => ['Content-Type' => 'application/ld+json']]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => [
                '@vocab' => 'http://example.com/docs.jsonld#',
                'hydra' => 'http://www.w3.org/ns/hydra/core#',
                'title' => 'Playlist/title',
                'description' => 'Playlist/description',
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
        $response = static::createClient()->request('POST', '/v1/playlists', [
            'json' => [
                'title' => 'Test playlist',
                'description' => 'This is a test playlist',
                'modifiedBy' => 'Test Tester',
                'createdBy' => 'Hans Tester',
                'published' => [
                    'from' => '2021-09-21T17:00:01Z',
                    'to' => '2021-07-22T17:00:01Z',
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
                'title' => 'Playlist/title',
                'description' => 'Playlist/description',
                'created' => 'Playlist/created',
                'modified' => 'Playlist/modified',
                'modifiedBy' => 'Playlist/modifiedBy',
                'createdBy' => 'Playlist/createdBy',
                'slides' => 'Playlist/slides',
            ],
            '@type' => 'Playlist',
            'title' => 'Test playlist',
            'description' => 'This is a test playlist',
            'modifiedBy' => 'Test Tester',
            'createdBy' => 'Hans Tester',
            'published' => [
                'from' => '2021-09-21T17:00:01Z',
                'to' => '2021-07-22T17:00:01Z',
            ],
        ]);
        $this->assertMatchesRegularExpression('@^/v\d/\w+/([A-Za-z0-9]{26})$@', $response->toArray()['@id']);

        // @TODO: published: Object value found, but an array is required
//        $this->assertMatchesResourceItemJsonSchema(Playlist::class);
    }

    public function testCreateInvalidPlaylist(): void
    {
        static::createClient()->request('POST', '/v1/playlists', [
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

    public function testCreateInvalidPlaylistTime(): void
    {
        static::createClient()->request('POST', '/v1/playlists', [
            'json' => [
                'published' => [
                    'from' => '2021-09-201T17:00:01Z',
                    'to' => '2021-42-22T17:00:01Z',
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

    public function testUpdatePlaylist(): void
    {
        $client = static::createClient();
        $iri = $this->findIriBy(Playlist::class, []);

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

    public function testDeletePlaylist(): void
    {
        $client = static::createClient();
        $iri = $this->findIriBy(Playlist::class, []);

        $client->request('DELETE', $iri);

        $this->assertResponseStatusCodeSame(204);

        $ulid = $this->utils->getUlidFromIRI($iri);
        $this->assertNull(
            static::getContainer()->get('doctrine')->getRepository(Playlist::class)->findOneBy(['id' => $ulid])
        );
    }

    public function testGetScreensList(): void
    {
        $client = static::createClient();
        $iri = $this->findIriBy(Playlist::class, []);
        $ulid = $this->utils->getUlidFromIRI($iri);

        $client->request('GET', '/v1/playlists/'.$ulid.'/screens', ['headers' => ['Content-Type' => 'application/ld+json']]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/Screen',
            '@id' => '/v1/screens',
            '@type' => 'hydra:Collection',
            'hydra:view' => [
                '@id' => '/v1/playlists/'.$ulid.'/screens?page=1',
                '@type' => 'hydra:PartialCollectionView',
                'hydra:first' => '/v1/playlists/'.$ulid.'/screens?page=1',
            ],
        ]);
    }

    public function testGetSlidesList(): void
    {
        $client = static::createClient();
        $iri = $this->findIriBy(Playlist::class, []);
        $ulid = $this->utils->getUlidFromIRI($iri);

        $client->request('GET', '/v1/playlists/'.$ulid.'/slides?page=1&itemsPerPage=10', ['headers' => ['Content-Type' => 'application/ld+json']]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/Slide',
            '@id' => '/v1/slides',
            '@type' => 'hydra:Collection',
            'hydra:view' => [
                '@id' => '/v1/playlists/'.$ulid.'/slides?itemsPerPage=10',
                '@type' => 'hydra:PartialCollectionView',
            ],
        ]);
    }

    public function testLinkSlide(): void
    {
        $client = static::createClient();

        $iri = $this->findIriBy(Playlist::class, []);
        $playlistUlid = $this->utils->getUlidFromIRI($iri);

        $iri = $this->findIriBy(Slide::class, []);
        $slideUlid = $this->utils->getUlidFromIRI($iri);

        $client->request('PUT', '/v1/playlists/'.$playlistUlid.'/slide/'.$slideUlid, [
            'json' => [
                'weight' => 10,
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/json');

        /** @var Playlist $playlist */
        $playlist = static::getContainer()->get('doctrine')->getRepository(Playlist::class)->findOneBy(['id' => $playlistUlid]);
        $slide = static::getContainer()->get('doctrine')->getRepository(Slide::class)->findOneBy(['id' => $slideUlid]);
        $this->assertEquals(true, $playlist->getPlaylistSlides()->exists(function (int $key, PlaylistSlide $playlistSlide) use ($slide) {
            return $playlistSlide->getSlide() === $slide;
        }));
    }

    public function testUnlinkSlide(): void
    {
        $client = static::createClient();

        $iri = $this->findIriBy(Playlist::class, []);
        $playlistUlid = $this->utils->getUlidFromIRI($iri);

        $iri = $this->findIriBy(Slide::class, []);
        $slideUlid = $this->utils->getUlidFromIRI($iri);

        // First link slides to ensure link exits and test it do exists.
        $client->request('PUT', '/v1/playlists/'.$playlistUlid.'/slide/'.$slideUlid, [
            'json' => [
                'weight' => 10,
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);

        $playlist = static::getContainer()->get('doctrine')->getRepository(Playlist::class)->findOneBy(['id' => $playlistUlid]);
        $slide = static::getContainer()->get('doctrine')->getRepository(Slide::class)->findOneBy(['id' => $slideUlid]);
        $this->assertEquals(true, $playlist->getPlaylistSlides()->exists(function (int $key, PlaylistSlide $playlistSlide) use ($slide) {
            return $playlistSlide->getSlide() === $slide;
        }));

        // Unlink and test it have been unlinked.
        $client->request('DELETE', '/v1/playlists/'.$playlistUlid.'/slide/'.$slideUlid, [
            'json' => [],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseStatusCodeSame(204);

        $playlist = static::getContainer()->get('doctrine')->getRepository(Playlist::class)->findOneBy(['id' => $playlistUlid]);
        $slide = static::getContainer()->get('doctrine')->getRepository(Slide::class)->findOneBy(['id' => $slideUlid]);
        $this->assertEquals(false, $playlist->getPlaylistSlides()->exists(function (int $key, PlaylistSlide $playlistSlide) use ($slide) {
            return $playlistSlide->getSlide() === $slide;
        }));
    }
}
