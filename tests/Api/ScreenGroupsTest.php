<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Entity\Tenant\Screen;
use App\Entity\Tenant\ScreenGroup;
use App\Tests\AbstractBaseApiTestCase;
use App\Utils\Roles;

class ScreenGroupsTest extends AbstractBaseApiTestCase
{
    public function testGetCollection(): void
    {
        $response = $this->getAuthenticatedClient('ROLE_SCREEN')->request('GET', '/v2/screen-groups?itemsPerPage=2', ['headers' => ['Content-Type' => 'application/ld+json']]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ScreenGroup',
            '@id' => '/v2/screen-groups',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 20,
            'hydra:view' => [
                '@id' => '/v2/screen-groups?itemsPerPage=2&page=1',
                '@type' => 'hydra:PartialCollectionView',
                'hydra:first' => '/v2/screen-groups?itemsPerPage=2&page=1',
                'hydra:last' => '/v2/screen-groups?itemsPerPage=2&page=10',
                'hydra:next' => '/v2/screen-groups?itemsPerPage=2&page=2',
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
        $response = $this->getAuthenticatedClient('ROLE_ADMIN')->request('POST', '/v2/screen-groups', [
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
        $this->getAuthenticatedClient('ROLE_ADMIN')->request('POST', '/v2/screen-groups', [
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

        $client->request('GET', '/v2/screens/'.$ulid.'/screen-groups?itemsPerPage=2&page=1', ['headers' => ['Content-Type' => 'application/ld+json']]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ScreenGroup',
            '@id' => '/v2/screens/'.$ulid.'/screen-groups',
            '@type' => 'hydra:Collection',
            'hydra:view' => [
                '@id' => '/v2/screens/'.$ulid.'/screen-groups?itemsPerPage=2',
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

        $client->request('PUT', '/v2/screens/'.$screenUlid.'/screen-groups', [
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

        $client->request('PUT', '/v2/screens/'.$screenUlid.'/screen-groups', [
            'json' => [
                $screenGroupUlid,
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $client->request('DELETE', '/v2/screens/'.$screenUlid.'/screen-groups/'.$screenGroupUlid, [
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

    public function testScreenGroupAuthorizationRoleEditor(): void
    {
        $client = $this->getAuthenticatedClient(Roles::ROLE_EDITOR);
        $iri = $this->findIriBy(ScreenGroup::class, ['tenant' => $this->tenant]);

        // Test GET collection - ROLE_EDITOR should have access
        $client->request('GET', '/v2/screen-groups', ['headers' => ['Content-Type' => 'application/ld+json']]);
        $this->assertResponseIsSuccessful();

        // Test GET item - ROLE_EDITOR should have access
        $client->request('GET', $iri, ['headers' => ['Content-Type' => 'application/ld+json']]);
        $this->assertResponseIsSuccessful();

        // Test POST - ROLE_EDITOR should have access
        $response = $client->request('POST', '/v2/screen-groups', [
            'json' => [
                'title' => 'Test screen group for ROLE_EDITOR',
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $createdIri = $response->toArray()['@id'];

        // Test PUT - ROLE_EDITOR should have access
        $client->request('PUT', $createdIri, [
            'json' => [
                'title' => 'Updated by ROLE_EDITOR',
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);
        $this->assertResponseIsSuccessful();

        // Test DELETE - ROLE_EDITOR should have access
        $client->request('DELETE', $createdIri);
        $this->assertResponseStatusCodeSame(204);
    }

    public function testScreenGroupAuthorizationRoleUser(): void
    {
        $client = $this->getAuthenticatedClient(Roles::ROLE_USER);
        $iri = $this->findIriBy(ScreenGroup::class, ['tenant' => $this->tenant]);

        // Test GET collection - ROLE_USER should be denied
        $client->request('GET', '/v2/screen-groups', ['headers' => ['Content-Type' => 'application/ld+json']]);
        $this->assertResponseStatusCodeSame(403);

        // Test GET item - ROLE_USER should be denied
        $client->request('GET', $iri, ['headers' => ['Content-Type' => 'application/ld+json']]);
        $this->assertResponseStatusCodeSame(403);

        // Test POST - ROLE_USER should be denied
        $client->request('POST', '/v2/screen-groups', [
            'json' => [
                'title' => 'Test screen group for ROLE_USER',
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);
        $this->assertResponseStatusCodeSame(403);

        // Test PUT - ROLE_USER should be denied
        $client->request('PUT', $iri, [
            'json' => [
                'title' => 'Updated by ROLE_USER',
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);
        $this->assertResponseStatusCodeSame(403);

        // Test DELETE - ROLE_USER should be denied
        $client->request('DELETE', $iri);
        $this->assertResponseStatusCodeSame(403);
    }
}
