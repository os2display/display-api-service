<?php

declare(strict_types=1);

namespace App\Tests\Api;

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

        $iri = $this->findIriBy(Playlist::class, ['tenant' => $this->tenant]);
        $playlistUlid = $this->iriHelperUtils->getUlidFromIRI($iri);

        $iri = $this->findIriBy(Screen::class, ['tenant' => $this->tenant]);
        $screenUlid1 = $this->iriHelperUtils->getUlidFromIRI($iri);
        $iri = $this->findIriBy(Screen::class, ['tenant' => $this->tenant]);
        $screenUlid2 = $this->iriHelperUtils->getUlidFromIRI($iri);

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

        $iri = $this->findIriBy(Playlist::class, ['tenant' => $this->tenant]);
        $playlistUlid = $this->iriHelperUtils->getUlidFromIRI($iri);

        $iri = $this->findIriBy(Screen::class, ['tenant' => $this->tenant]);
        $screenUlid1 = $this->iriHelperUtils->getUlidFromIRI($iri);
        $iri = $this->findIriBy(Screen::class, ['tenant' => $this->tenant]);
        $screenUlid2 = $this->iriHelperUtils->getUlidFromIRI($iri);

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
}
