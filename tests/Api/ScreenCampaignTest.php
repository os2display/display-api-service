<?php

namespace App\Tests\Api;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Playlist;
use App\Entity\Screen;
use App\Entity\ScreenCampaign;
use App\Tests\BaseTestTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Uid\Ulid;

class ScreenCampaignTest extends ApiTestCase
{
    use BaseTestTrait;

    public function testLinkPlaylistToScreen(): void
    {
        $client = static::createClient();

        $iri = $this->findIriBy(Screen::class, []);
        $screenUlid = $this->iriHelperUtils->getUlidFromIRI($iri);

        $iri = $this->findIriBy(Playlist::class, []);
        $playlistUlid1 = $this->iriHelperUtils->getUlidFromIRI($iri);
        $iri = $this->findIriBy(Playlist::class, []);
        $playlistUlid2 = $this->iriHelperUtils->getUlidFromIRI($iri);

        $client->request('PUT', '/v1/screens/'.$screenUlid.'/campaigns', [
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

        $relations = static::getContainer()->get('doctrine')->getRepository(ScreenCampaign::class)->findBy(['screen' => $screenUlid]);
        $relations = new ArrayCollection($relations);

        $this->assertEquals(2, $relations->count());

        $this->assertEquals(true, $relations->exists(function (int $key, ScreenCampaign $screenCampaign) use ($playlistUlid1) {
            return $screenCampaign->getCampaign()->getId()->equals(Ulid::fromString($playlistUlid1));
        }));

        $this->assertEquals(true, $relations->exists(function (int $key, ScreenCampaign $screenCampaign) use ($playlistUlid2) {
            return $screenCampaign->getCampaign()->getId()->equals(Ulid::fromString($playlistUlid2));
        }));
    }

    public function testGetSlidesList(): void
    {
        $client = static::createClient();
        $iri = $this->findIriBy(Screen::class, []);
        $ulid = $this->iriHelperUtils->getUlidFromIRI($iri);

        $client->request('GET', '/v1/screens/'.$ulid.'/campaigns?page=1&itemsPerPage=10', ['headers' => ['Content-Type' => 'application/ld+json']]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ScreenCampaign',
            '@id' => '/v1/screen-campaigns',
            '@type' => 'hydra:Collection',
        ]);
    }

    public function testUnlinkPlaylist(): void
    {
        $client = static::createClient();

        $iri = $this->findIriBy(Screen::class, []);
        $screenUlid = $this->iriHelperUtils->getUlidFromIRI($iri);

        $iri = $this->findIriBy(Playlist::class, []);
        $playlistUlid1 = $this->iriHelperUtils->getUlidFromIRI($iri);
        $iri = $this->findIriBy(Playlist::class, []);
        $playlistUlid2 = $this->iriHelperUtils->getUlidFromIRI($iri);

        // First create relations to ensure they exist before deleting theme.
        $client->request('PUT', '/v1/screens/'.$screenUlid.'/campaigns', [
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

        $client->request('DELETE', '/v1/screens/'.$screenUlid.'/campaigns/'.$playlistUlid1, [
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);
        $this->assertResponseStatusCodeSame(204);
        $client->request('DELETE', '/v1/screens/'.$screenUlid.'/campaigns/'.$playlistUlid2, [
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);
        $this->assertResponseStatusCodeSame(204);

        $relations = static::getContainer()->get('doctrine')->getRepository(ScreenCampaign::class)->findBy(['screen' => $screenUlid]);
        $this->assertIsArray($relations);
        $this->assertEmpty($relations);
    }
}
