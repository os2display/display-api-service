<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Entity\ScreenLayout;
use App\Entity\ScreenLayoutRegions;
use App\Entity\Tenant\Playlist;
use App\Entity\Tenant\PlaylistScreenRegion;
use App\Entity\Tenant\Screen;
use App\Entity\Tenant\ScreenGroup;
use App\Tests\AbstractBaseApiTestCase;
use Doctrine\ORM\EntityManager;

class ScreensTest extends AbstractBaseApiTestCase
{
    private ?EntityManager $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        $kernel = self::bootKernel();

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();
    }

    public function testGetCollection(): void
    {
        $response = $this->getAuthenticatedClient('ROLE_ADMIN')->request('GET', '/v2/screens?itemsPerPage=5', ['headers' => ['Content-Type' => 'application/ld+json']]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/Screen',
            '@id' => '/v2/screens',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 10,
            'hydra:view' => [
                '@id' => '/v2/screens?itemsPerPage=5&page=1',
                '@type' => 'hydra:PartialCollectionView',
                'hydra:first' => '/v2/screens?itemsPerPage=5&page=1',
                'hydra:last' => '/v2/screens?itemsPerPage=5&page=2',
                'hydra:next' => '/v2/screens?itemsPerPage=5&page=2',
            ],
        ]);

        $this->assertCount(5, $response->toArray()['hydra:member']);

        $this->assertMatchesResourceCollectionJsonSchema(Screen::class);
    }

    public function testGetItem(): void
    {
        $client = $this->getAuthenticatedClient('ROLE_ADMIN');
        $iri = $this->findIriBy(Screen::class, ['tenant' => $this->tenant]);

        $client->request('GET', $iri, ['headers' => ['Content-Type' => 'application/ld+json']]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => [
                '@vocab' => 'http://localhost/docs.jsonld#',
                'hydra' => 'http://www.w3.org/ns/hydra/core#',
                'title' => 'Screen/title',
                'description' => 'Screen/description',
                'size' => 'Screen/size',
                'created' => 'Screen/created',
                'modified' => 'Screen/modified',
                'modifiedBy' => 'Screen/modifiedBy',
                'createdBy' => 'Screen/createdBy',
                'layout' => 'Screen/layout',
                'location' => 'Screen/location',
                'regions' => 'Screen/regions',
                'inScreenGroups' => 'Screen/inScreenGroups',
                'resolution' => 'Screen/resolution',
                'orientation' => 'Screen/orientation',
            ],
            '@type' => 'Screen',
            '@id' => $iri,
        ]);
    }

    public function testCreateScreen(): void
    {
        $client = $this->getAuthenticatedClient('ROLE_ADMIN');

        $layoutIri = $this->findIriBy(ScreenLayout::class, ['title' => '2 boxes']);
        $regionIriLeft = $this->findIriBy(ScreenLayoutRegions::class, ['title' => 'Left']);
        $regionIriRight = $this->findIriBy(ScreenLayoutRegions::class, ['title' => 'Right']);
        $screenGroupOneIri = $this->findIriBy(ScreenGroup::class, ['title' => 'screen_group_abc_1']);
        $screenGroupTwoIri = $this->findIriBy(ScreenGroup::class, ['title' => 'screen_group_abc_2']);
        $playlistIri = $this->findIriBy(Playlist::class, ['title' => 'playlist_abc_3']);

        $regionUlidLeft = $this->iriHelperUtils->getUlidFromIRI($regionIriLeft);
        $regionUlidRight = $this->iriHelperUtils->getUlidFromIRI($regionIriRight);
        $screenGroupOneUlid = $this->iriHelperUtils->getUlidFromIRI($screenGroupOneIri);
        $screenGroupTwoUlid = $this->iriHelperUtils->getUlidFromIRI($screenGroupTwoIri);
        $playlistUlid = $this->iriHelperUtils->getUlidFromIRI($playlistIri);

        $response = $client->request('POST', '/v2/screens', [
            'json' => [
                'title' => 'Test screen 42',
                'description' => 'This is a test screen',
                'size' => '65',
                'layout' => $layoutIri,
                'location' => 'M2.42',
                'resolution' => '4K',
                'orientation' => 'vertical',
                'groups' => [$screenGroupOneUlid, $screenGroupTwoUlid],
                'enableColorSchemeChange' => true,
                'regions' => [['playlists' => [['id' => $playlistUlid, 'weight' => 0]], 'regionId' => $regionUlidLeft], ['playlists' => [['id' => $playlistUlid, 'weight' => 0]], 'regionId' => $regionUlidRight]],
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $this->assertJsonContains([
            '@context' => [
                '@vocab' => 'http://localhost/docs.jsonld#',
                'hydra' => 'http://www.w3.org/ns/hydra/core#',
                'title' => 'Screen/title',
                'description' => 'Screen/description',
                'size' => 'Screen/size',
                'created' => 'Screen/created',
                'modified' => 'Screen/modified',
                'modifiedBy' => 'Screen/modifiedBy',
                'createdBy' => 'Screen/createdBy',
                'layout' => 'Screen/layout',
                'location' => 'Screen/location',
                'regions' => 'Screen/regions',
                'inScreenGroups' => 'Screen/inScreenGroups',
                'resolution' => 'Screen/resolution',
                'orientation' => 'Screen/orientation',
            ],
            '@type' => 'Screen',
            'title' => 'Test screen 42',
            'description' => 'This is a test screen',
            'size' => '65',
            'modifiedBy' => 'test@example.com',
            'createdBy' => 'test@example.com',
            'layout' => $layoutIri,
            'location' => 'M2.42',
            'resolution' => '4K',
            'orientation' => 'vertical',
            'inScreenGroups' => '/v2/screens/'.$response->toArray()['id'].'/screen-groups',
            'enableColorSchemeChange' => true,
        ]);

        $regions = $response->toArray()['regions'];
        $this->assertTrue(in_array('/v2/screens/'.$response->toArray()['id'].'/regions/'.$regionUlidLeft.'/playlists', $regions));
        $this->assertTrue(in_array('/v2/screens/'.$response->toArray()['id'].'/regions/'.$regionUlidRight.'/playlists', $regions));

        $this->assertMatchesRegularExpression('@^/v\d/\w+/([A-Za-z0-9]{26})$@', $response->toArray()['@id']);

        $this->assertMatchesResourceItemJsonSchema(Screen::class);
    }

    public function testCreateInvalidScreen(): void
    {
        $this->getAuthenticatedClient('ROLE_ADMIN')->request('POST', '/v2/screens', [
            'json' => [
                'title' => 123_456_789,
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $this->assertJsonContains([
            '@context' => '/contexts/Error',
            '@type' => 'hydra:Error',
            'hydra:title' => 'An error occurred',
            'hydra:description' => 'The input data is misformatted.',
        ]);
    }

    public function testUpdateScreen(): void
    {
        $client = $this->getAuthenticatedClient('ROLE_ADMIN');

        $playlistScreenRegionRepository = $this->entityManager->getRepository(PlaylistScreenRegion::class);
        $playlistScreenRegionCountBefore = $playlistScreenRegionRepository->count([]);

        $playlistIri = $this->findIriBy(Playlist::class, ['title' => 'playlist_screen_test_update']);
        $playlistUlid = $this->iriHelperUtils->getUlidFromIRI($playlistIri);
        $regionIri = $this->findIriBy(ScreenLayoutRegions::class, ['title' => 'full']);
        $regionUlid = $this->iriHelperUtils->getUlidFromIRI($regionIri);
        $screenIri = $this->findIriBy(Screen::class, ['title' => 'screen_test_update']);

        $client->request('PUT', $screenIri, [
            'json' => [
                'title' => 'Updated title',
                'regions' => [['playlists' => [['id' => $playlistUlid, 'weight' => 0]], 'regionId' => $regionUlid]],
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@type' => 'Screen',
            '@id' => $screenIri,
            'title' => 'Updated title',
            'regions' => [$screenIri.'/regions/'.$regionUlid.'/playlists'],
        ]);
        $playlistScreenRegionCountAfter = $playlistScreenRegionRepository->count([]);
        $this->assertEquals($playlistScreenRegionCountBefore, $playlistScreenRegionCountAfter, 'PlaylistScreenRegion count should not change');

        // Test that PUT without regions property will not change playlist screen regions.

        $client->request('PUT', $screenIri, [
            'json' => [
                'title' => 'Updated title 2',
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@id' => $screenIri,
            'title' => 'Updated title 2',
        ]);
        $playlistScreenRegionCountAfter = $playlistScreenRegionRepository->count([]);
        $this->assertEquals($playlistScreenRegionCountBefore, $playlistScreenRegionCountAfter, 'PlaylistScreenRegion count should not change');

        // Test that region can be cleared.

        $client->request('PUT', $screenIri, [
            'json' => [
                'title' => 'Updated title 3',
                'regions' => [['playlists' => [], 'regionId' => $regionUlid]],
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@id' => $screenIri,
            'title' => 'Updated title 3',
        ]);
        $playlistScreenRegionCountAfter = $playlistScreenRegionRepository->count([]);
        $this->assertEquals($playlistScreenRegionCountBefore - 1, $playlistScreenRegionCountAfter, 'PlaylistScreenRegion count should go 1 down');
    }

    public function testCampaignsLengthAndActiveCampaignsLength(): void
    {
        $client = $this->getAuthenticatedClient('ROLE_ADMIN');

        $layoutIri = $this->findIriBy(ScreenLayout::class, ['title' => 'full screen']);

        // Create a fresh screen with no campaigns and no screen groups
        $screenResponse = $client->request('POST', '/v2/screens', [
            'json' => [
                'title' => 'Test campaignsLength screen',
                'layout' => $layoutIri,
            ],
            'headers' => ['Content-Type' => 'application/ld+json'],
        ]);
        $this->assertResponseStatusCodeSame(201);

        $screenIri = $screenResponse->toArray()['@id'];
        $screenUlid = $this->iriHelperUtils->getUlidFromIRI($screenIri);

        // Initial state: all counts are 0
        $client->request('GET', $screenIri, ['headers' => ['Content-Type' => 'application/ld+json']]);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'campaignsLength' => 0,
            'activeCampaignsLength' => 0,
            'inScreenGroupsLength' => 0,
        ]);

        // Create an active campaign: publishedFrom in past, publishedTo in far future
        $activeCampaignResponse = $client->request('POST', '/v2/playlists', [
            'json' => [
                'title' => 'Active campaign',
                'isCampaign' => true,
                'published' => [
                    'from' => '2020-01-01T00:00:00.000Z',
                    'to' => '2099-01-01T00:00:00.000Z',
                ],
            ],
            'headers' => ['Content-Type' => 'application/ld+json'],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $activeCampaignIri = $activeCampaignResponse->toArray()['@id'];
        $activeCampaignUlid = $this->iriHelperUtils->getUlidFromIRI($activeCampaignIri);

        // Create an inactive campaign: publishedFrom in far future (not yet started)
        $inactiveCampaignResponse = $client->request('POST', '/v2/playlists', [
            'json' => [
                'title' => 'Inactive campaign',
                'isCampaign' => true,
                'published' => [
                    'from' => '2099-01-01T00:00:00.000Z',
                    'to' => '2099-06-01T00:00:00.000Z',
                ],
            ],
            'headers' => ['Content-Type' => 'application/ld+json'],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $inactiveCampaignIri = $inactiveCampaignResponse->toArray()['@id'];
        $inactiveCampaignUlid = $this->iriHelperUtils->getUlidFromIRI($inactiveCampaignIri);

        // Link active campaign to the screen.
        // Note: PUT /v2/screens/{campaignId}/campaigns is campaign-centric — {id} is the campaign ULID,
        // and the body specifies which screens should display it.
        $client->request('PUT', '/v2/screens/'.$activeCampaignUlid.'/campaigns', [
            'json' => [(object) ['screen' => $screenUlid]],
            'headers' => ['Content-Type' => 'application/ld+json'],
        ]);
        $this->assertResponseStatusCodeSame(201);

        $client->request('GET', $screenIri, ['headers' => ['Content-Type' => 'application/ld+json']]);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'campaignsLength' => 1,
            'activeCampaignsLength' => 1,
            'inScreenGroupsLength' => 0,
        ]);

        // Link inactive campaign to the screen
        $client->request('PUT', '/v2/screens/'.$inactiveCampaignUlid.'/campaigns', [
            'json' => [(object) ['screen' => $screenUlid]],
            'headers' => ['Content-Type' => 'application/ld+json'],
        ]);
        $this->assertResponseStatusCodeSame(201);

        $client->request('GET', $screenIri, ['headers' => ['Content-Type' => 'application/ld+json']]);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'campaignsLength' => 2,
            'activeCampaignsLength' => 1,
            'inScreenGroupsLength' => 0,
        ]);

        // Create an empty screen group (no campaigns) and add the screen to it
        $screenGroupResponse = $client->request('POST', '/v2/screen-groups', [
            'json' => ['title' => 'Test screen group for inScreenGroupsLength'],
            'headers' => ['Content-Type' => 'application/ld+json'],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $screenGroupIri = $screenGroupResponse->toArray()['@id'];
        $screenGroupUlid = $this->iriHelperUtils->getUlidFromIRI($screenGroupIri);

        $client->request('PUT', '/v2/screens/'.$screenUlid.'/screen-groups', [
            'json' => [$screenGroupUlid],
            'headers' => ['Content-Type' => 'application/ld+json'],
        ]);
        $this->assertResponseStatusCodeSame(201);

        $client->request('GET', $screenIri, ['headers' => ['Content-Type' => 'application/ld+json']]);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'campaignsLength' => 2,
            'activeCampaignsLength' => 1,
            'inScreenGroupsLength' => 1,
        ]);

        // Cleanup
        $client->request('DELETE', '/v2/screens/'.$screenUlid.'/campaigns/'.$inactiveCampaignUlid);
        $this->assertResponseStatusCodeSame(204);

        $client->request('DELETE', '/v2/screens/'.$screenUlid.'/campaigns/'.$activeCampaignUlid);
        $this->assertResponseStatusCodeSame(204);

        $client->request('DELETE', '/v2/screens/'.$screenUlid.'/screen-groups/'.$screenGroupUlid);
        $this->assertResponseStatusCodeSame(204);

        $client->request('DELETE', $screenIri);
        $this->assertResponseStatusCodeSame(204);

        $client->request('DELETE', $screenGroupIri);
        $this->assertResponseStatusCodeSame(204);

        $client->request('DELETE', $activeCampaignIri);
        $this->assertResponseStatusCodeSame(204);

        $client->request('DELETE', $inactiveCampaignIri);
        $this->assertResponseStatusCodeSame(204);
    }

    public function testCampaignsLengthViaScreenGroupAndDeduplication(): void
    {
        $client = $this->getAuthenticatedClient('ROLE_ADMIN');

        $layoutIri = $this->findIriBy(ScreenLayout::class, ['title' => 'full screen']);

        // Create a fresh screen
        $screenResponse = $client->request('POST', '/v2/screens', [
            'json' => [
                'title' => 'Test indirect campaigns screen',
                'layout' => $layoutIri,
            ],
            'headers' => ['Content-Type' => 'application/ld+json'],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $screenIri = $screenResponse->toArray()['@id'];
        $screenUlid = $this->iriHelperUtils->getUlidFromIRI($screenIri);

        // Create a screen group
        $screenGroupResponse = $client->request('POST', '/v2/screen-groups', [
            'json' => ['title' => 'Test indirect campaigns group'],
            'headers' => ['Content-Type' => 'application/ld+json'],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $screenGroupIri = $screenGroupResponse->toArray()['@id'];
        $screenGroupUlid = $this->iriHelperUtils->getUlidFromIRI($screenGroupIri);

        // Create an active campaign
        $campaignResponse = $client->request('POST', '/v2/playlists', [
            'json' => [
                'title' => 'Indirect campaign',
                'isCampaign' => true,
                'published' => [
                    'from' => '2020-01-01T00:00:00.000Z',
                    'to' => '2099-01-01T00:00:00.000Z',
                ],
            ],
            'headers' => ['Content-Type' => 'application/ld+json'],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $campaignIri = $campaignResponse->toArray()['@id'];
        $campaignUlid = $this->iriHelperUtils->getUlidFromIRI($campaignIri);

        // Add screen to the screen group
        $client->request('PUT', '/v2/screens/'.$screenUlid.'/screen-groups', [
            'json' => [$screenGroupUlid],
            'headers' => ['Content-Type' => 'application/ld+json'],
        ]);
        $this->assertResponseStatusCodeSame(201);

        // Link campaign to the screen group (indirect path)
        $client->request('PUT', '/v2/screen-groups/'.$campaignUlid.'/campaigns', [
            'json' => [(object) ['screengroup' => $screenGroupUlid]],
            'headers' => ['Content-Type' => 'application/ld+json'],
        ]);
        $this->assertResponseStatusCodeSame(201);

        // Verify the indirect campaign is counted
        $client->request('GET', $screenIri, ['headers' => ['Content-Type' => 'application/ld+json']]);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'campaignsLength' => 1,
            'activeCampaignsLength' => 1,
        ]);

        // Now also link the same campaign directly to the screen
        $client->request('PUT', '/v2/screens/'.$campaignUlid.'/campaigns', [
            'json' => [(object) ['screen' => $screenUlid]],
            'headers' => ['Content-Type' => 'application/ld+json'],
        ]);
        $this->assertResponseStatusCodeSame(201);

        // Verify deduplication: campaign is linked both directly and via group,
        // but COUNT(DISTINCT) should count it only once
        $client->request('GET', $screenIri, ['headers' => ['Content-Type' => 'application/ld+json']]);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'campaignsLength' => 1,
            'activeCampaignsLength' => 1,
        ]);

        // Cleanup
        $client->request('DELETE', '/v2/screens/'.$screenUlid.'/campaigns/'.$campaignUlid);
        $this->assertResponseStatusCodeSame(204);

        $client->request('DELETE', '/v2/screen-groups/'.$screenGroupUlid.'/campaigns/'.$campaignUlid);
        $this->assertResponseStatusCodeSame(204);

        $client->request('DELETE', '/v2/screens/'.$screenUlid.'/screen-groups/'.$screenGroupUlid);
        $this->assertResponseStatusCodeSame(204);

        $client->request('DELETE', $screenIri);
        $this->assertResponseStatusCodeSame(204);

        $client->request('DELETE', $screenGroupIri);
        $this->assertResponseStatusCodeSame(204);

        $client->request('DELETE', $campaignIri);
        $this->assertResponseStatusCodeSame(204);
    }

    public function testDeleteScreen(): void
    {
        $client = $this->getAuthenticatedClient('ROLE_ADMIN');
        $iri = $this->findIriBy(Screen::class, ['tenant' => $this->tenant]);

        $client->request('DELETE', $iri);

        $this->assertResponseStatusCodeSame(204);

        $ulid = $this->iriHelperUtils->getUlidFromIRI($iri);
        $this->assertNull(
            static::getContainer()->get('doctrine')->getRepository(Screen::class)->findOneBy(['id' => $ulid])
        );
    }
}
