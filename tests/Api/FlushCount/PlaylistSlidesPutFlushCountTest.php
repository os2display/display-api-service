<?php

declare(strict_types=1);

namespace App\Tests\Api\FlushCount;

use App\Entity\Tenant\Playlist;
use App\Entity\Tenant\Slide;

/**
 * PUT /v2/playlists/{id}/slides.
 */
class PlaylistSlidesPutFlushCountTest extends AbstractFlushCountTestCase
{
    public function testPutPlaylistSlides(): void
    {
        $client = $this->getAuthenticatedClient();

        $playlistUlid = $this->iriHelperUtils->getUlidFromIRI($this->findIriBy(Playlist::class, ['title' => 'playlist_abc_1']));
        $slideUlid1 = $this->iriHelperUtils->getUlidFromIRI($this->findIriBy(Slide::class, ['title' => 'slide_abc_1']));
        $slideUlid2 = $this->iriHelperUtils->getUlidFromIRI($this->findIriBy(Slide::class, ['title' => 'slide_abc_2']));

        $this->resetProbes();
        $client->request('PUT', '/v2/playlists/'.$playlistUlid.'/slides', [
            'json' => [
                (object) ['slide' => $slideUlid1, 'weight' => 10],
                (object) ['slide' => $slideUlid2, 'weight' => 20],
            ],
            'headers' => ['Content-Type' => 'application/ld+json'],
        ]);
        $this->assertResponseStatusCodeSame(201);

        // One flush, inside the repository's manual transaction.
        $this->assertProbes(1, [1]);
    }
}
