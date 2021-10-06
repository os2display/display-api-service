<?php

namespace App\Tests\Api;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Screen;
use App\Entity\ScreenGroup;
use App\Tests\BaseTestTrait;

class ScreenGroupsTest extends ApiTestCase
{
    use BaseTestTrait;

    public function testGetCollection(): void
    {
        $response = static::createClient()->request('GET', '/v1/screen-groups?itemsPerPage=2', ['headers' => ['Content-Type' => 'application/ld+json']]);

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
        $client = static::createClient();
        $iri = $this->findIriBy(ScreenGroup::class, []);

        $client->request('GET', $iri, ['headers' => ['Content-Type' => 'application/ld+json']]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => [
                '@vocab' => 'http://example.com/docs.jsonld#',
                'hydra' => 'http://www.w3.org/ns/hydra/core#',
                'title' => 'ScreenGroup/title',
                'description' => 'ScreenGroup/description',
                'created' => 'ScreenGroup/created',
                'modified' => 'ScreenGroup/modified',
                'modifiedBy' => 'ScreenGroup/modifiedBy',
                'createdBy' => 'ScreenGroup/createdBy',
            ],
            '@type' => 'ScreenGroup',
            '@id' => $iri,
        ]);
    }

    public function testCreateScreenGroup(): void
    {
        $response = static::createClient()->request('POST', '/v1/screen-groups', [
            'json' => [
                'title' => 'Test groups',
                'description' => 'This is a test screen group',
                'modifiedBy' => 'Test Tester',
                'createdBy' => 'Hans Tester',
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => [
                '@vocab' => 'http://example.com/docs.jsonld#',
                'hydra' => 'http://www.w3.org/ns/hydra/core#',
                'title' => 'ScreenGroup/title',
                'description' => 'ScreenGroup/description',
                'created' => 'ScreenGroup/created',
                'modified' => 'ScreenGroup/modified',
                'modifiedBy' => 'ScreenGroup/modifiedBy',
                'createdBy' => 'ScreenGroup/createdBy',
            ],
            '@type' => 'ScreenGroup',
            'title' => 'Test groups',
            'description' => 'This is a test screen group',
            'modifiedBy' => 'Test Tester',
            'createdBy' => 'Hans Tester',
        ]);
        $this->assertMatchesRegularExpression('@^/v\d/[A-Za-z-]+/([A-Za-z0-9]{26})$@', $response->toArray()['@id']);
        $this->assertMatchesResourceItemJsonSchema(ScreenGroup::class);
    }

    public function testCreateInvalidScreenGroup(): void
    {
        static::createClient()->request('POST', '/v1/screen-groups', [
            'json' => [
                'title' => 123456789,
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
        $client = static::createClient();
        $iri = $this->findIriBy(ScreenGroup::class, []);

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
        $client = static::createClient();
        $iri = $this->findIriBy(ScreenGroup::class, []);

        $client->request('DELETE', $iri);

        $this->assertResponseStatusCodeSame(204);

        $ulid = $this->iriHelperUtils->getUlidFromIRI($iri);
        $this->assertNull(
            static::getContainer()->get('doctrine')->getRepository(ScreenGroup::class)->findOneBy(['id' => $ulid])
        );
    }

    public function testGetScreenGroupsScreenRelations(): void
    {
        $client = static::createClient();

        $iri = $this->findIriBy(Screen::class, []);
        $ulid = $this->iriHelperUtils->getUlidFromIRI($iri);

        $client->request('GET', '/v1/screens/'.$ulid.'/screen-groups?itemsPerPage=2', ['headers' => ['Content-Type' => 'application/ld+json']]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/ScreenGroup',
            '@id' => '/v1/screen-groups',
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
        $client = static::createClient();

        $iri = $this->findIriBy(Screen::class, []);
        $screenUlid = $this->iriHelperUtils->getUlidFromIRI($iri);

        $iri = $this->findIriBy(ScreenGroup::class, []);
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
        $client = static::createClient();

        $iri = $this->findIriBy(Screen::class, []);
        $screenUlid = $this->iriHelperUtils->getUlidFromIRI($iri);

        $iri = $this->findIriBy(ScreenGroup::class, []);
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
