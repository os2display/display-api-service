<?php

declare(strict_types=1);

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Entity\Tenant;
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

    public function testPutReplacesExistingRelations(): void
    {
        $client = $this->getAuthenticatedClient('ROLE_ADMIN');
        $campaignUlid = $this->getCampaignUlid();
        [$screenGroupUlid1, $screenGroupUlid2] = $this->getScreenGroupUlids();

        $this->putScreenGroups($client, $campaignUlid, [$screenGroupUlid1, $screenGroupUlid2]);
        $this->assertResponseStatusCodeSame(201);

        $this->putScreenGroups($client, $campaignUlid, [$screenGroupUlid2]);
        $this->assertResponseStatusCodeSame(201);

        $this->assertSame([$screenGroupUlid2], $this->getLinkedScreenGroupUlids($campaignUlid));
    }

    public function testPutWithEmptyArrayRemovesAllRelations(): void
    {
        $client = $this->getAuthenticatedClient('ROLE_ADMIN');
        $campaignUlid = $this->getCampaignUlid();
        [$screenGroupUlid1] = $this->getScreenGroupUlids();

        $this->putScreenGroups($client, $campaignUlid, [$screenGroupUlid1]);
        $this->assertResponseStatusCodeSame(201);

        $this->putScreenGroups($client, $campaignUlid, []);
        $this->assertResponseStatusCodeSame(201);

        $this->assertSame([], $this->getLinkedScreenGroupUlids($campaignUlid));
    }

    public function testPutWithSameSelectionKeepsRelations(): void
    {
        $client = $this->getAuthenticatedClient('ROLE_ADMIN');
        $campaignUlid = $this->getCampaignUlid();
        [$screenGroupUlid1, $screenGroupUlid2] = $this->getScreenGroupUlids();

        $this->putScreenGroups($client, $campaignUlid, [$screenGroupUlid1, $screenGroupUlid2]);
        $this->assertResponseStatusCodeSame(201);

        $this->putScreenGroups($client, $campaignUlid, [$screenGroupUlid1, $screenGroupUlid2]);
        $this->assertResponseStatusCodeSame(201);

        $this->assertEqualsCanonicalizing([$screenGroupUlid1, $screenGroupUlid2], $this->getLinkedScreenGroupUlids($campaignUlid));
    }

    public function testCannotLinkScreenGroupFromAnotherTenant(): void
    {
        $client = $this->getAuthenticatedClient('ROLE_ADMIN');
        $campaignUlid = $this->getCampaignUlid();
        [$screenGroupUlid1] = $this->getScreenGroupUlids();
        $xyzScreenGroupUlid = (string) static::getContainer()->get('doctrine')->getRepository(ScreenGroup::class)->findOneBy(['tenant' => $this->getXyzTenant()])->getId();

        $this->putScreenGroups($client, $campaignUlid, [$screenGroupUlid1]);
        $this->assertResponseStatusCodeSame(201);

        $this->putScreenGroups($client, $campaignUlid, [$xyzScreenGroupUlid]);
        // Pins current behaviour, not intended: 404 would not reveal that the resource exists in another tenant.
        $this->assertResponseStatusCodeSame(400);

        $this->assertSame([$screenGroupUlid1], $this->getLinkedScreenGroupUlids($campaignUlid));
    }

    public function testCannotLinkToCampaignFromAnotherTenant(): void
    {
        $client = $this->getAuthenticatedClient('ROLE_ADMIN');
        $xyzCampaign = static::getContainer()->get('doctrine')->getRepository(Playlist::class)->findOneBy(['tenant' => $this->getXyzTenant(), 'isCampaign' => true]);
        $this->assertTrue($xyzCampaign->getIsCampaign());
        $xyzCampaignUlid = (string) $xyzCampaign->getId();
        [$screenGroupUlid1] = $this->getScreenGroupUlids();

        $this->putScreenGroups($client, $xyzCampaignUlid, [$screenGroupUlid1]);
        // Pins current behaviour, not intended: 404 would not reveal that the resource exists in another tenant.
        $this->assertResponseStatusCodeSame(400);

        $relations = static::getContainer()->get('doctrine')->getRepository(ScreenGroupCampaign::class)->findBy(['campaign' => $xyzCampaignUlid, 'screenGroup' => $screenGroupUlid1]);
        $this->assertEmpty($relations);
    }

    public function testCannotUnlinkCampaignFromAnotherTenant(): void
    {
        $client = $this->getAuthenticatedClient('ROLE_ADMIN');
        $em = static::getContainer()->get('doctrine')->getManager();
        $repository = $em->getRepository(ScreenGroupCampaign::class);
        $relation = $repository->findOneBy(['tenant' => $this->getXyzTenant()]);
        $this->assertNotNull($relation);
        $relationId = $relation->getId();

        $client->request('DELETE', '/v2/screen-groups/'.$relation->getScreenGroup()->getId().'/campaigns/'.$relation->getCampaign()->getId(), [
            'headers' => ['Content-Type' => 'application/ld+json'],
        ]);
        // Pins current behaviour, not intended: 404 would not reveal that the resource exists in another tenant.
        $this->assertResponseStatusCodeSame(400);

        $em->clear();
        $this->assertNotNull($repository->find($relationId));
    }

    /**
     * @param list<string> $screenGroupUlids
     */
    private function putScreenGroups(Client $client, string $campaignUlid, array $screenGroupUlids): void
    {
        $client->request('PUT', '/v2/screen-groups/'.$campaignUlid.'/campaigns', [
            'json' => array_map(fn (string $ulid) => (object) ['screengroup' => $ulid], $screenGroupUlids),
            'headers' => ['Content-Type' => 'application/ld+json'],
        ]);
    }

    /**
     * @return list<string>
     */
    private function getLinkedScreenGroupUlids(string $campaignUlid): array
    {
        $relations = static::getContainer()->get('doctrine')->getRepository(ScreenGroupCampaign::class)->findBy(['campaign' => $campaignUlid]);

        return array_map(fn (ScreenGroupCampaign $relation) => (string) $relation->getScreenGroup()->getId(), $relations);
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
        $this->assertCount(2, $screenGroups);

        return array_map(fn (ScreenGroup $screenGroup) => (string) $screenGroup->getId(), $screenGroups);
    }

    private function getXyzTenant(): Tenant
    {
        return static::getContainer()->get('doctrine')->getRepository(Tenant::class)->findOneBy(['tenantKey' => 'XYZ']);
    }
}
