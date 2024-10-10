<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Entity\ScreenLayout;
use App\Entity\ScreenLayoutRegions;
use App\Entity\Tenant\Playlist;
use App\Entity\Tenant\Screen;
use App\Entity\Tenant\ScreenGroup;
use App\Tests\AbstractBaseApiTestCase;

class ScreensTest extends AbstractBaseApiTestCase
{
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
            'regions' => ['/v2/screens/'.$response->toArray()['id'].'/regions/'.$regionUlidLeft.'/playlists', '/v2/screens/'.$response->toArray()['id'].'/regions/'.$regionUlidRight.'/playlists'],
        ]);
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
        $iri = $this->findIriBy(Screen::class, ['tenant' => $this->tenant]);

        $client->request('PUT', $iri, [
            'json' => [
                'title' => 'Updated title',
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@type' => 'Screen',
            '@id' => $iri,
            'title' => 'Updated title',
        ]);
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
