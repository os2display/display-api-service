<?php

namespace App\Tests\Api;

use App\Entity\ScreenLayoutRegions;
use App\Entity\Tenant\Playlist;
use App\Entity\Tenant\PlaylistScreenRegion;
use App\Entity\Tenant\Screen;
use App\Tests\AbstractBaseApiTestCase;

class PlaylistScreenRegionTest extends AbstractBaseApiTestCase
{
    public function testGetPlaylistsInScreenRegion(): void
    {
        $this->markTestSkipped(sprintf('FIXME: %s', __METHOD__));
        $client = $this->getAuthenticatedClient('ROLE_ADMIN');

        $iri = $this->findIriBy(Screen::class, ['tenant' => $this->tenant]);
        $screenUlid = $this->iriHelperUtils->getUlidFromIRI($iri);

        $iri = $this->findIriBy(ScreenLayoutRegions::class, []);
        $regionUlid = $this->iriHelperUtils->getUlidFromIRI($iri);

        $url = '/v1/screens/'.$screenUlid.'/regions/'.$regionUlid.'/playlists?itemsPerPage=5';
        $client->request('GET', $url, ['headers' => ['Content-Type' => 'application/ld+json']]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/PlaylistScreenRegion',
            '@id' => '/v1/playlist-screen-regions',
            '@type' => 'hydra:Collection',
        ]);
    }

    public function testLinkRegionPlaylist(): void
    {
        $client = $this->getAuthenticatedClient('ROLE_ADMIN');

        $iri = $this->findIriBy(Screen::class, ['tenant' => $this->tenant]);
        $screenUlid = $this->iriHelperUtils->getUlidFromIRI($iri);

        $iri = $this->findIriBy(ScreenLayoutRegions::class, []);
        $regionsUlid = $this->iriHelperUtils->getUlidFromIRI($iri);

        $iri = $this->findIriBy(Playlist::class, ['tenant' => $this->tenant]);
        $playlistUlid = $this->iriHelperUtils->getUlidFromIRI($iri);

        $url = '/v1/screens/'.$screenUlid.'/regions/'.$regionsUlid.'/playlists';
        $client->request('PUT', $url, [
            'json' => [
                (object) [
                    'playlist' => $playlistUlid,
                    'weight' => 10,
                ],
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/json');

        /** @var Playlist $playlist */
        $link = static::getContainer()->get('doctrine')->getRepository(PlaylistScreenRegion::class)->findOneBy([
            'playlist' => $playlistUlid,
            'screen' => $screenUlid,
            'region' => $regionsUlid,
        ]);
        $this->assertnotEquals(null, $link);
    }

    public function testUnlinkRegionPlaylist(): void
    {
        $client = $this->getAuthenticatedClient('ROLE_ADMIN');

        $iri = $this->findIriBy(Screen::class, ['tenant' => $this->tenant]);
        $screenUlid = $this->iriHelperUtils->getUlidFromIRI($iri);

        $iri = $this->findIriBy(Playlist::class, ['tenant' => $this->tenant]);
        $playlistUlid = $this->iriHelperUtils->getUlidFromIRI($iri);

        $iri = $this->findIriBy(ScreenLayoutRegions::class, []);
        $regionsUlid = $this->iriHelperUtils->getUlidFromIRI($iri);

        $url = '/v1/screens/'.$screenUlid.'/regions/'.$regionsUlid.'/playlists';

        // Ensure link exists and is created before deleting it.
        $client->request('PUT', $url, [
            'json' => [
                (object) [
                    'playlist' => $playlistUlid,
                    'weight' => 10,
                ],
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $link = static::getContainer()->get('doctrine')->getRepository(PlaylistScreenRegion::class)->findOneBy([
            'playlist' => $playlistUlid,
            'screen' => $screenUlid,
            'region' => $regionsUlid,
        ]);
        $this->assertnotEquals(null, $link);

        // Remove the link just created.
        $client->request('DELETE', $url.'/'.$playlistUlid, [
            'json' => [],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);
        $this->assertResponseStatusCodeSame(204);

        $unlink = static::getContainer()->get('doctrine')->getRepository(PlaylistScreenRegion::class)->findOneBy([
            'playlist' => $playlistUlid,
            'screen' => $screenUlid,
            'region' => $regionsUlid,
        ]);
        $this->assertEquals(null, $unlink);
    }
}
