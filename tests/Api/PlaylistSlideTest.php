<?php

namespace App\Tests\Api;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Playlist;
use App\Entity\PlaylistSlide;
use App\Entity\Slide;
use App\Tests\BaseTestTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Uid\Ulid;

class PlaylistSlideTest extends ApiTestCase
{
    use BaseTestTrait;

    public function testLinkSlideToPlaylist(): void
    {
        $client = static::createClient();

        $iri = $this->findIriBy(Playlist::class, []);
        $playlistUlid = $this->utils->getUlidFromIRI($iri);

        $iri = $this->findIriBy(Slide::class, []);
        $slideUlid1 = $this->utils->getUlidFromIRI($iri);
        $iri = $this->findIriBy(Slide::class, []);
        $slideUlid2 = $this->utils->getUlidFromIRI($iri);

        $client->request('PUT', '/v1/playlists/'.$playlistUlid.'/slides', [
            'json' => [
                (object) [
                  'slide' => $slideUlid1,
                  'weight' => 42,
                ],
                (object) [
                  'slide' => $slideUlid2,
                  'weight' => 84,
                ],
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $relations = static::getContainer()->get('doctrine')->getRepository(PlaylistSlide::class)->findBy(['playlist' => $playlistUlid]);
        $relations = new ArrayCollection($relations);

        $this->assertEquals(2, $relations->count());

        $this->assertEquals(true, $relations->exists(function (int $key, PlaylistSlide $playlistSlide) use ($slideUlid1) {
            return $playlistSlide->getSlide()->getId()->equals(Ulid::fromString($slideUlid1));
        }));

        $this->assertEquals(true, $relations->exists(function (int $key, PlaylistSlide $playlistSlide) use ($slideUlid2) {
            return $playlistSlide->getSlide()->getId()->equals(Ulid::fromString($slideUlid2));
        }));
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
            '@context' => '/contexts/PlaylistSlide',
            '@id' => '/v1/playlist-slides',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 2,
        ]);
    }

    public function testUnlinkSlide(): void
    {
        $client = static::createClient();

        $iri = $this->findIriBy(Playlist::class, []);
        $playlistUlid = $this->utils->getUlidFromIRI($iri);

        $iri = $this->findIriBy(Slide::class, []);
        $slideUlid1 = $this->utils->getUlidFromIRI($iri);
        $iri = $this->findIriBy(Slide::class, []);
        $slideUlid2 = $this->utils->getUlidFromIRI($iri);

        // First create relations to ensure they exist before deleting theme.
        $client->request('PUT', '/v1/playlists/'.$playlistUlid.'/slides', [
            'json' => [
                (object) [
                    'slide' => $slideUlid1,
                    'weight' => 42,
                ],
                (object) [
                    'slide' => $slideUlid2,
                    'weight' => 84,
                ],
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);

        $client->request('DELETE', '/v1/playlists/'.$playlistUlid.'/slides/'.$slideUlid1, [
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);
        $this->assertResponseStatusCodeSame(204);
        $client->request('DELETE', '/v1/playlists/'.$playlistUlid.'/slides/'.$slideUlid2, [
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);
        $this->assertResponseStatusCodeSame(204);

        $relations = static::getContainer()->get('doctrine')->getRepository(PlaylistSlide::class)->findBy(['playlist' => $playlistUlid]);
        $this->assertIsArray($relations);
        $this->assertEmpty($relations);
    }
}
