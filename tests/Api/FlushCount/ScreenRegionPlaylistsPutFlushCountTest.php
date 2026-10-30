<?php

declare(strict_types=1);

namespace App\Tests\Api\FlushCount;

use App\Entity\ScreenLayoutRegions;
use App\Entity\Tenant\Playlist;
use App\Entity\Tenant\Screen;

/**
 * PUT /v2/screens/{id}/regions/{regionId}/playlists.
 */
class ScreenRegionPlaylistsPutFlushCountTest extends AbstractFlushCountTestCase
{
    public function testPutScreenRegionPlaylists(): void
    {
        $client = $this->getAuthenticatedClient('ROLE_ADMIN');

        $screenUlid = $this->iriHelperUtils->getUlidFromIRI($this->findIriBy(Screen::class, ['title' => 'screen_abc_1']));
        // screen_abc_1's fixture placement is in the full region, so the removal flush has a placement to remove.
        $regionUlid = $this->iriHelperUtils->getUlidFromIRI($this->findIriBy(ScreenLayoutRegions::class, ['title' => 'full']));
        $playlistUlid1 = $this->iriHelperUtils->getUlidFromIRI($this->findIriBy(Playlist::class, ['title' => 'playlist_abc_1']));
        $playlistUlid2 = $this->iriHelperUtils->getUlidFromIRI($this->findIriBy(Playlist::class, ['title' => 'playlist_abc_2']));

        $this->resetProbes();
        $client->request('PUT', '/v2/screens/'.$screenUlid.'/regions/'.$regionUlid.'/playlists', [
            'json' => [
                (object) ['playlist' => $playlistUlid1, 'weight' => 10],
                (object) ['playlist' => $playlistUlid2, 'weight' => 20],
            ],
            'headers' => ['Content-Type' => 'application/ld+json'],
        ]);
        $this->assertResponseStatusCodeSame(201);

        // Removal flush, then insert flush, both inside the repository's manual transaction.
        $this->assertProbes(2, [1, 1]);
    }
}
