<?php

declare(strict_types=1);

namespace App\Tests\Api\FlushCount;

use App\Entity\Tenant\Playlist;

/**
 * PUT /v2/playlists/{id} with tenants removed.
 */
class PlaylistPutTenantsRemovedFlushCountTest extends AbstractFlushCountTestCase
{
    public function testPutPlaylistWithTenantRemoved(): void
    {
        $client = $this->getAuthenticatedClient('ROLE_ADMIN');

        // playlist_abc_1 is placed on screen_abc_1 in tenant ABC (fixtures/playlist_screen_region.yaml).
        $iri = $this->findIriBy(Playlist::class, ['title' => 'playlist_abc_1']);
        $tenantId = (string) $this->tenant->getId();

        // Share the playlist with tenant ABC, so removing that tenant removes its placements.
        $client->request('PUT', $iri, [
            'json' => ['title' => 'playlist_abc_1', 'tenants' => [$tenantId]],
            'headers' => ['Content-Type' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();

        $this->resetProbes();
        $client->request('PUT', $iri, [
            'json' => ['title' => 'playlist_abc_1', 'tenants' => []],
            'headers' => ['Content-Type' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();

        // PlaylistScreenRegionRepository::deleteRelationsPlaylistsTenant() flushes before the persist processor.
        $this->assertProbes(2, [0, 0]);
    }
}
