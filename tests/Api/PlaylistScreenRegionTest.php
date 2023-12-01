<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Entity\ScreenLayoutRegions;
use App\Entity\Tenant\Playlist;
use App\Entity\Tenant\PlaylistScreenRegion;
use App\Entity\Tenant\Screen;
use App\Repository\PlaylistScreenRegionRepository;
use App\Tests\AbstractBaseApiTestCase;

class PlaylistScreenRegionTest extends AbstractBaseApiTestCase
{
    public function testGetPlaylistsInScreenRegion(): void
    {
        $client = $this->getAuthenticatedClient('ROLE_ADMIN');
        $container = $this::getContainer();

        $repo = $container->get(PlaylistScreenRegionRepository::class);
        $playlistScreenRegion = $repo->findOneBy(['tenant' => $this->tenant]);

        $screenUlid = $playlistScreenRegion->getScreen()->getId();
        $regionUlid = $playlistScreenRegion->getRegion()->getId();
        $playlist = $playlistScreenRegion->getPlaylist();
        $url = '/v1/screens/'.$screenUlid.'/regions/'.$regionUlid.'/playlists';
        $client->request('GET', $url, ['headers' => ['Content-Type' => 'application/ld+json']]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/PlaylistScreenRegion',
            '@id' => '/v1/screens/'.$screenUlid.'/regions/'.$regionUlid.'/playlists',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 1,
            'hydra:member' => [
                [
                    // Testing for a subset of the folded out playlist
                    'playlist' => [
                        '@id' => '/v1/playlists/' . $playlist->getId(),
                        'title' => $playlist->getTitle(),
                        'description' => $playlist->getDescription(),
                        'published' => [
                            'from' => $playlist->getPublishedFrom()->format('Y-m-d\TH:i:s.v\Z'),
                            'to' => $playlist->getPublishedTo()->format('Y-m-d\TH:i:s.v\Z'),
                        ],
                        'slides' => '/v1/playlists/'.$playlist->getId().'/slides',
                    ],
                    'weight' => $playlistScreenRegion->getWeight(),
                ]
            ]
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
