<?php

declare(strict_types=1);

namespace App\Tests\Api\FlushCount;

use App\Entity\Tenant\Playlist;
use App\Entity\Tenant\Slide;

/**
 * PUT /v2/slides/{id}/playlists.
 */
class SlidePlaylistsPutFlushCountTest extends AbstractFlushCountTestCase
{
    public function testPutSlidePlaylists(): void
    {
        $client = $this->getAuthenticatedClient();

        $slideUlid = $this->iriHelperUtils->getUlidFromIRI($this->findIriBy(Slide::class, ['title' => 'slide_abc_2']));
        $playlistUlid1 = $this->iriHelperUtils->getUlidFromIRI($this->findIriBy(Playlist::class, ['title' => 'playlist_abc_1']));
        $playlistUlid2 = $this->iriHelperUtils->getUlidFromIRI($this->findIriBy(Playlist::class, ['title' => 'playlist_abc_2']));

        $this->resetProbes();
        $client->request('PUT', '/v2/slides/'.$slideUlid.'/playlists', [
            'json' => [
                (object) ['playlist' => $playlistUlid1],
                (object) ['playlist' => $playlistUlid2],
            ],
            'headers' => ['Content-Type' => 'application/ld+json'],
        ]);
        $this->assertResponseStatusCodeSame(201);

        // One flush, inside the repository's manual transaction.
        $this->assertProbes(1, [1]);
    }
}
