<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Entity\Tenant\Playlist;
use App\Entity\Tenant\ScreenGroup;
use App\Entity\Tenant\ScreenGroupCampaign;
use App\Tests\AbstractBaseApiTestCase;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Uid\Ulid;

class ScreenGroupCampaignTest extends AbstractBaseApiTestCase
{
    public function testLinkPlaylistToScreenGroup(): void
    {
        $client = $this->getAuthenticatedClient('ROLE_ADMIN');

        $playlistUlid = $this->getCampaignUlid();
        [$screenGroupUlid1, $screenGroupUlid2] = $this->getScreenGroupUlids();
        $this->assertNotEquals($screenGroupUlid1, $screenGroupUlid2);

        $client->request('PUT', '/v2/screen-groups/'.$playlistUlid.'/campaigns', [
            'json' => [
                (object) [
                    'screengroup' => $screenGroupUlid1,
                ],
                (object) [
                    'screengroup' => $screenGroupUlid2,
                ],
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $relations = static::getContainer()->get('doctrine')->getRepository(ScreenGroupCampaign::class)->findBy(['campaign' => $playlistUlid]);
        $relations = new ArrayCollection($relations);

        $this->assertEquals(2, $relations->count());

        $this->assertEquals(true, $relations->exists(fn (int $key, ScreenGroupCampaign $screenGroupCampaign) => $screenGroupCampaign->getScreenGroup()->getId()->equals(Ulid::fromString($screenGroupUlid1))));

        $this->assertEquals(true, $relations->exists(fn (int $key, ScreenGroupCampaign $screenGroupCampaign) => $screenGroupCampaign->getScreenGroup()->getId()->equals(Ulid::fromString($screenGroupUlid2))));
    }

    public function testGetSlidesList(): void
    {
        $client = $this->getAuthenticatedClient('ROLE_SCREEN');
        $iri = $this->findIriBy(ScreenGroup::class, ['tenant' => $this->tenant]);
        $ulid = $this->iriHelperUtils->getUlidFromIRI($iri);

        $client->request('GET', '/v2/screen-groups/'.$ulid.'/campaigns?page=1&itemsPerPage=10', ['headers' => ['Content-Type' => 'application/ld+json']]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ScreenGroupCampaign',
            '@id' => '/v2/screen-groups/'.$ulid.'/campaigns',
            '@type' => 'hydra:Collection',
        ]);
    }

    public function testUnlinkPlaylist(): void
    {
        $client = $this->getAuthenticatedClient('ROLE_ADMIN');

        $playlistUlid = $this->getCampaignUlid();
        [$screenGroupUlid1, $screenGroupUlid2] = $this->getScreenGroupUlids();
        $this->assertNotEquals($screenGroupUlid1, $screenGroupUlid2);

        $client->request('PUT', '/v2/screen-groups/'.$playlistUlid.'/campaigns', [
            'json' => [
                (object) [
                    'screengroup' => $screenGroupUlid1,
                ],
                (object) [
                    'screengroup' => $screenGroupUlid2,
                ],
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);

        $client->request('DELETE', '/v2/screen-groups/'.$screenGroupUlid1.'/campaigns/'.$playlistUlid, [
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);
        $this->assertResponseStatusCodeSame(204);
        $client->request('DELETE', '/v2/screen-groups/'.$screenGroupUlid2.'/campaigns/'.$playlistUlid, [
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);
        $this->assertResponseStatusCodeSame(204);

        $relations = static::getContainer()->get('doctrine')->getRepository(ScreenGroupCampaign::class)->findBy(['campaign' => $playlistUlid]);
        $this->assertIsArray($relations);
        $this->assertEmpty($relations);
    }

    private function getCampaignUlid(): string
    {
        $iri = $this->findIriBy(Playlist::class, ['tenant' => $this->tenant, 'isCampaign' => true]);

        return $this->iriHelperUtils->getUlidFromIRI($iri);
    }

    /**
     * @return list<string>
     */
    private function getScreenGroupUlids(): array
    {
        $screenGroups = static::getContainer()->get('doctrine')->getRepository(ScreenGroup::class)->findBy(['tenant' => $this->tenant], null, 2);

        return array_map(fn (ScreenGroup $screenGroup) => (string) $screenGroup->getId(), $screenGroups);
    }
}
