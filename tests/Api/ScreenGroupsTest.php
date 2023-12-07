<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Entity\Tenant\Screen;
use App\Entity\Tenant\ScreenGroup;
use App\Tests\AbstractBaseApiTestCase;

class ScreenGroupsTest extends AbstractBaseApiTestCase
{
    public function testGetCollection(): void
    {
        $response = $this->getAuthenticatedClient('ROLE_SCREEN')->request('GET', '/v1/screen-groups?itemsPerPage=2', ['headers' => ['Content-Type' => 'application/ld+json']]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ScreenGroup',
            '@id' => '/v1/screen-groups',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 20,
            'hydra:view' => [
                '@id' => '/v1/screen-groups?itemsPerPage=2&page=1',
                '@type' => 'hydra:PartialCollectionView',
                'hydra:first' => '/v1/screen-groups?itemsPerPage=2&page=1',
                'hydra:last' => '/v1/screen-groups?itemsPerPage=2&page=10',
                'hydra:next' => '/v1/screen-groups?itemsPerPage=2&page=2',
            ],
        ]);

        $this->assertCount(2, $response->toArray()['hydra:member']);
        $this->assertMatchesResourceCollectionJsonSchema(ScreenGroup::class);
    }

    public function testGetItem(): void
    {
        $client = $this->getAuthenticatedClient('ROLE_SCREEN');
        $iri = $this->findIriBy(ScreenGroup::class, ['tenant' => $this->tenant]);

        $client->request('GET', $iri, ['headers' => ['Content-Type' => 'application/ld+json']]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ScreenGroup',
            '@type' => 'ScreenGroup',
            '@id' => $iri,
        ]);
    }

    public function testCreateScreenGroup(): void
    {
        $response = $this->getAuthenticatedClient('ROLE_ADMIN')->request('POST', '/v1/screen-groups', [
            'json' => [
                'title' => 'Test groups',
                'description' => 'This is a test screen group',
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ScreenGroup',
            '@type' => 'ScreenGroup',
            'title' => 'Test groups',
            'description' => 'This is a test screen group',
            'modifiedBy' => 'test@example.com',
            'createdBy' => 'test@example.com',
        ]);
        $this->assertMatchesRegularExpression('@^/v\d/[A-Za-z-]+/([A-Za-z0-9]{26})$@', $response->toArray()['@id']);
        $this->assertMatchesResourceItemJsonSchema(ScreenGroup::class);
    }

    public function testCreateInvalidScreenGroup(): void
    {
        $this->getAuthenticatedClient('ROLE_ADMIN')->request('POST', '/v1/screen-groups', [
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

    public function testUpdateScreenGroup(): void
    {
        $client = $this->getAuthenticatedClient('ROLE_ADMIN');
        $iri = $this->findIriBy(ScreenGroup::class, ['tenant' => $this->tenant]);

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
            '@type' => 'ScreenGroup',
            '@id' => $iri,
            'title' => 'Updated title',
        ]);
    }

    public function testDeleteScreenGroup(): void
    {
        $client = $this->getAuthenticatedClient('ROLE_ADMIN');
        $iri = $this->findIriBy(ScreenGroup::class, ['tenant' => $this->tenant]);

        $client->request('DELETE', $iri);

        $this->assertResponseStatusCodeSame(204);

        $ulid = $this->iriHelperUtils->getUlidFromIRI($iri);
        $this->assertNull(
            static::getContainer()->get('doctrine')->getRepository(ScreenGroup::class)->findOneBy(['id' => $ulid])
        );
    }

    public function testGetScreenGroupsScreenRelations(): void
    {
        $client = $this->getAuthenticatedClient('ROLE_SCREEN');

        // A random ULID.
        $ulid = '01FKZZ3HHK2ESG3PMV2KXTX5QY';

        $client->request('GET', '/v1/screens/'.$ulid.'/screen-groups?itemsPerPage=2&page=1', ['headers' => ['Content-Type' => 'application/ld+json']]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ScreenGroup',
            '@id' => '/v1/screens/'.$ulid.'/screen-groups',
            '@type' => 'hydra:Collection',
            'hydra:view' => [
                '@id' => '/v1/screens/'.$ulid.'/screen-groups?itemsPerPage=2',
                '@type' => 'hydra:PartialCollectionView',
            ],
        ]);

        $this->assertMatchesResourceCollectionJsonSchema(ScreenGroup::class);
    }

    public function testCreateScreenGroupsScreenRelations(): void
    {
        $client = $this->getAuthenticatedClient('ROLE_ADMIN');

        $iri = $this->findIriBy(Screen::class, ['tenant' => $this->tenant]);
        $screenUlid = $this->iriHelperUtils->getUlidFromIRI($iri);

        $iri = $this->findIriBy(ScreenGroup::class, ['tenant' => $this->tenant]);
        $screenGroupUlid = $this->iriHelperUtils->getUlidFromIRI($iri);

        $client->request('PUT', '/v1/screens/'.$screenUlid.'/screen-groups', [
            'json' => [
                $screenGroupUlid,
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $screen = static::getContainer()->get('doctrine')->getRepository(Screen::class)->findBy(['id' => $screenUlid]);
        $screen = reset($screen);

        $screenGroup = static::getContainer()->get('doctrine')->getRepository(ScreenGroup::class)->findBy(['id' => $screenGroupUlid]);
        $screenGroup = reset($screenGroup);

        $this->assertTrue($screen->getScreenGroups()->contains($screenGroup));
    }

    public function testDeleteScreenGroupsScreenRelations(): void
    {
        $client = $this->getAuthenticatedClient('ROLE_ADMIN');

        $iri = $this->findIriBy(Screen::class, ['tenant' => $this->tenant]);
        $screenUlid = $this->iriHelperUtils->getUlidFromIRI($iri);

        $iri = $this->findIriBy(ScreenGroup::class, ['tenant' => $this->tenant]);
        $screenGroupUlid = $this->iriHelperUtils->getUlidFromIRI($iri);

        $client->request('PUT', '/v1/screens/'.$screenUlid.'/screen-groups', [
            'json' => [
                $screenGroupUlid,
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $client->request('DELETE', '/v1/screens/'.$screenUlid.'/screen-groups/'.$screenGroupUlid, [
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);
        $this->assertResponseStatusCodeSame(204);

        $screen = static::getContainer()->get('doctrine')->getRepository(Screen::class)->findBy(['id' => $screenUlid]);
        $screen = reset($screen);

        $screenGroup = static::getContainer()->get('doctrine')->getRepository(ScreenGroup::class)->findBy(['id' => $screenGroupUlid]);
        $screenGroup = reset($screenGroup);

        $this->assertFalse($screen->getScreenGroups()->contains($screenGroup));
    }
}
