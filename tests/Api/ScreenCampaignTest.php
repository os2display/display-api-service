<?php

declare(strict_types=1);

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Entity\Tenant;
use App\Entity\Tenant\Playlist;
use App\Entity\Tenant\Screen;
use App\Entity\Tenant\ScreenCampaign;
use App\Tests\AbstractBaseApiTestCase;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Uid\Ulid;

class ScreenCampaignTest extends AbstractBaseApiTestCase
{
    public function testLinkPlaylistToScreen(): void
    {
        $client = $this->getAuthenticatedClient('ROLE_ADMIN');

        $playlistUlid = $this->getCampaignUlid();
        [$screenUlid1, $screenUlid2] = $this->getScreenUlids();

        $client->request('PUT', '/v2/screens/'.$playlistUlid.'/campaigns', [
            'json' => [
                (object) [
                    'screen' => $screenUlid1,
                ],
                (object) [
                    'screen' => $screenUlid2,
                ],
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $relations = static::getContainer()->get('doctrine')->getRepository(ScreenCampaign::class)->findBy(['campaign' => $playlistUlid]);
        $relations = new ArrayCollection($relations);

        $this->assertEquals(2, $relations->count());

        $this->assertEquals(true, $relations->exists(fn (int $key, ScreenCampaign $screenCampaign) => $screenCampaign->getScreen()->getId()->equals(Ulid::fromString($screenUlid1))));

        $this->assertEquals(true, $relations->exists(fn (int $key, ScreenCampaign $screenCampaign) => $screenCampaign->getScreen()->getId()->equals(Ulid::fromString($screenUlid2))));
    }

    public function testGetSlidesList(): void
    {
        $client = $this->getAuthenticatedClient('ROLE_SCREEN');
        $iri = $this->findIriBy(Screen::class, ['tenant' => $this->tenant]);
        $ulid = $this->iriHelperUtils->getUlidFromIRI($iri);

        $client->request('GET', '/v2/screens/'.$ulid.'/campaigns?page=1&itemsPerPage=10', ['headers' => ['Content-Type' => 'application/ld+json']]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ScreenCampaign',
            '@id' => '/v2/screens/'.$ulid.'/campaigns',
            '@type' => 'hydra:Collection',
        ]);
    }

    public function testUnlinkPlaylist(): void
    {
        $client = $this->getAuthenticatedClient('ROLE_ADMIN');

        $playlistUlid = $this->getCampaignUlid();
        [$screenUlid1, $screenUlid2] = $this->getScreenUlids();

        $client->request('PUT', '/v2/screens/'.$playlistUlid.'/campaigns', [
            'json' => [
                (object) [
                    'screen' => $screenUlid1,
                ],
                (object) [
                    'screen' => $screenUlid2,
                ],
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);

        $client->request('DELETE', '/v2/screens/'.$screenUlid1.'/campaigns/'.$playlistUlid, [
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);
        $this->assertResponseStatusCodeSame(204);
        $client->request('DELETE', '/v2/screens/'.$screenUlid2.'/campaigns/'.$playlistUlid, [
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);
        $this->assertResponseStatusCodeSame(204);

        $relations = static::getContainer()->get('doctrine')->getRepository(ScreenCampaign::class)->findBy(['campaign' => $playlistUlid]);
        $this->assertIsArray($relations);
        $this->assertEmpty($relations);
    }

    public function testPutReplacesExistingRelations(): void
    {
        $client = $this->getAuthenticatedClient('ROLE_ADMIN');
        $campaignUlid = $this->getCampaignUlid();
        [$screenUlid1, $screenUlid2] = $this->getScreenUlids();

        $this->putScreens($client, $campaignUlid, [$screenUlid1, $screenUlid2]);
        $this->assertResponseStatusCodeSame(201);

        $this->putScreens($client, $campaignUlid, [$screenUlid2]);
        $this->assertResponseStatusCodeSame(201);

        $this->assertSame([$screenUlid2], $this->getLinkedScreenUlids($campaignUlid));
    }

    public function testPutWithEmptyArrayRemovesAllRelations(): void
    {
        $client = $this->getAuthenticatedClient('ROLE_ADMIN');
        $campaignUlid = $this->getCampaignUlid();
        [$screenUlid1] = $this->getScreenUlids();

        $this->putScreens($client, $campaignUlid, [$screenUlid1]);
        $this->assertResponseStatusCodeSame(201);

        $this->putScreens($client, $campaignUlid, []);
        $this->assertResponseStatusCodeSame(201);

        $this->assertSame([], $this->getLinkedScreenUlids($campaignUlid));
    }

    public function testPutWithSameSelectionKeepsRelations(): void
    {
        $client = $this->getAuthenticatedClient('ROLE_ADMIN');
        $campaignUlid = $this->getCampaignUlid();
        [$screenUlid1, $screenUlid2] = $this->getScreenUlids();

        $this->putScreens($client, $campaignUlid, [$screenUlid1, $screenUlid2]);
        $this->assertResponseStatusCodeSame(201);

        $this->putScreens($client, $campaignUlid, [$screenUlid1, $screenUlid2]);
        $this->assertResponseStatusCodeSame(201);

        $this->assertEqualsCanonicalizing([$screenUlid1, $screenUlid2], $this->getLinkedScreenUlids($campaignUlid));
    }

    public function testCannotLinkScreenFromAnotherTenant(): void
    {
        $client = $this->getAuthenticatedClient('ROLE_ADMIN');
        $campaignUlid = $this->getCampaignUlid();
        [$screenUlid1] = $this->getScreenUlids();
        $xyzScreenUlid = (string) static::getContainer()->get('doctrine')->getRepository(Screen::class)->findOneBy(['tenant' => $this->getXyzTenant()])->getId();

        $this->putScreens($client, $campaignUlid, [$screenUlid1]);
        $this->assertResponseStatusCodeSame(201);

        $this->putScreens($client, $campaignUlid, [$xyzScreenUlid]);
        $this->assertResponseStatusCodeSame(400);

        $this->assertSame([$screenUlid1], $this->getLinkedScreenUlids($campaignUlid));
    }

    public function testCannotLinkToCampaignFromAnotherTenant(): void
    {
        $client = $this->getAuthenticatedClient('ROLE_ADMIN');
        $xyzCampaign = static::getContainer()->get('doctrine')->getRepository(Playlist::class)->findOneBy(['tenant' => $this->getXyzTenant(), 'isCampaign' => true]);
        $this->assertTrue($xyzCampaign->getIsCampaign());
        $xyzCampaignUlid = (string) $xyzCampaign->getId();
        [$screenUlid1] = $this->getScreenUlids();

        $this->putScreens($client, $xyzCampaignUlid, [$screenUlid1]);
        $this->assertResponseStatusCodeSame(400);

        $relations = static::getContainer()->get('doctrine')->getRepository(ScreenCampaign::class)->findBy(['campaign' => $xyzCampaignUlid, 'screen' => $screenUlid1]);
        $this->assertEmpty($relations);
    }

    public function testCannotUnlinkCampaignFromAnotherTenant(): void
    {
        $client = $this->getAuthenticatedClient('ROLE_ADMIN');
        $em = static::getContainer()->get('doctrine')->getManager();
        $repository = $em->getRepository(ScreenCampaign::class);
        $relation = $repository->findOneBy(['tenant' => $this->getXyzTenant()]);
        $this->assertNotNull($relation);
        $relationId = $relation->getId();

        $client->request('DELETE', '/v2/screens/'.$relation->getScreen()->getId().'/campaigns/'.$relation->getCampaign()->getId(), [
            'headers' => ['Content-Type' => 'application/ld+json'],
        ]);
        $this->assertResponseStatusCodeSame(400);

        $em->clear();
        $this->assertNotNull($repository->find($relationId));
    }

    /**
     * @param list<string> $screenUlids
     */
    private function putScreens(Client $client, string $campaignUlid, array $screenUlids): void
    {
        $client->request('PUT', '/v2/screens/'.$campaignUlid.'/campaigns', [
            'json' => array_map(fn (string $ulid) => (object) ['screen' => $ulid], $screenUlids),
            'headers' => ['Content-Type' => 'application/ld+json'],
        ]);
    }

    /**
     * @return list<string>
     */
    private function getLinkedScreenUlids(string $campaignUlid): array
    {
        $relations = static::getContainer()->get('doctrine')->getRepository(ScreenCampaign::class)->findBy(['campaign' => $campaignUlid]);

        return array_map(fn (ScreenCampaign $relation) => (string) $relation->getScreen()->getId(), $relations);
    }

    private function getCampaignUlid(): string
    {
        $iri = $this->findIriBy(Playlist::class, ['tenant' => $this->tenant, 'isCampaign' => true]);

        return $this->iriHelperUtils->getUlidFromIRI($iri);
    }

    /**
     * @return list<string>
     */
    private function getScreenUlids(): array
    {
        $screens = static::getContainer()->get('doctrine')->getRepository(Screen::class)->findBy(['tenant' => $this->tenant], null, 2);
        $this->assertCount(2, $screens);

        return array_map(fn (Screen $screen) => (string) $screen->getId(), $screens);
    }

    private function getXyzTenant(): Tenant
    {
        return static::getContainer()->get('doctrine')->getRepository(Tenant::class)->findOneBy(['tenantKey' => 'XYZ']);
    }
}
