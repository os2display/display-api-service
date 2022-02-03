<?php

namespace App\Tests\Api;

use App\Entity\Playlist;
use App\Entity\ScreenGroup;
use App\Entity\ScreenGroupCampaign;
use App\Tests\AbstractBaseApiTestCase;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Uid\Ulid;

class ScreenGroupCampaignTest extends AbstractBaseApiTestCase
{
    public function testLinkPlaylistToScreenGroup(): void
    {
        $client = $this->getAuthenticatedClient();

        $iri = $this->findIriBy(ScreenGroup::class, []);
        $screenGroupUlid = $this->iriHelperUtils->getUlidFromIRI($iri);

        $iri = $this->findIriBy(Playlist::class, []);
        $playlistUlid1 = $this->iriHelperUtils->getUlidFromIRI($iri);
        $iri = $this->findIriBy(Playlist::class, []);
        $playlistUlid2 = $this->iriHelperUtils->getUlidFromIRI($iri);

        $client->request('PUT', '/v1/screen-groups/'.$screenGroupUlid.'/campaigns', [
            'json' => [
                (object) [
                  'playlist' => $playlistUlid1,
                ],
                (object) [
                  'playlist' => $playlistUlid2,
                ],
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $relations = static::getContainer()->get('doctrine')->getRepository(ScreenGroupCampaign::class)->findBy(['screenGroup' => $screenGroupUlid]);
        $relations = new ArrayCollection($relations);

        $this->assertEquals(2, $relations->count());

        $this->assertEquals(true, $relations->exists(function (int $key, ScreenGroupCampaign $screenGroupCampaign) use ($playlistUlid1) {
            return $screenGroupCampaign->getCampaign()->getId()->equals(Ulid::fromString($playlistUlid1));
        }));

        $this->assertEquals(true, $relations->exists(function (int $key, ScreenGroupCampaign $screenGroupCampaign) use ($playlistUlid2) {
            return $screenGroupCampaign->getCampaign()->getId()->equals(Ulid::fromString($playlistUlid2));
        }));
    }

    public function testGetSlidesList(): void
    {
        $client = $this->getAuthenticatedClient();
        $iri = $this->findIriBy(ScreenGroup::class, []);
        $ulid = $this->iriHelperUtils->getUlidFromIRI($iri);

        $client->request('GET', '/v1/screen-groups/'.$ulid.'/campaigns?page=1&itemsPerPage=10', ['headers' => ['Content-Type' => 'application/ld+json']]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ScreenGroupCampaign',
            '@id' => '/v1/screen-group-campaigns',
            '@type' => 'hydra:Collection',
        ]);
    }

    public function testUnlinkPlaylist(): void
    {
        $client = $this->getAuthenticatedClient();

        $iri = $this->findIriBy(ScreenGroup::class, []);
        $screenGroupUlid = $this->iriHelperUtils->getUlidFromIRI($iri);

        $iri = $this->findIriBy(Playlist::class, []);
        $playlistUlid1 = $this->iriHelperUtils->getUlidFromIRI($iri);
        $iri = $this->findIriBy(Playlist::class, []);
        $playlistUlid2 = $this->iriHelperUtils->getUlidFromIRI($iri);

        // First create relations to ensure they exist before deleting theme.
        $client->request('PUT', '/v1/screen-groups/'.$screenGroupUlid.'/campaigns', [
            'json' => [
                (object) [
                    'playlist' => $playlistUlid1,
                ],
                (object) [
                    'playlist' => $playlistUlid2,
                ],
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);

        $client->request('DELETE', '/v1/screen-groups/'.$screenGroupUlid.'/campaigns/'.$playlistUlid1, [
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);
        $this->assertResponseStatusCodeSame(204);
        $client->request('DELETE', '/v1/screen-groups/'.$screenGroupUlid.'/campaigns/'.$playlistUlid2, [
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);
        $this->assertResponseStatusCodeSame(204);

        $relations = static::getContainer()->get('doctrine')->getRepository(ScreenGroupCampaign::class)->findBy(['screenGroup' => $screenGroupUlid]);
        $this->assertIsArray($relations);
        $this->assertEmpty($relations);
    }
}
