<?php

namespace App\Tests\Api;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Slide;
use App\Entity\Template;
use App\Entity\Theme;
use App\Tests\BaseTestTrait;

class ThemesTest extends ApiTestCase
{
    use BaseTestTrait;

    public function testGetCollection(): void
    {
        $response = static::createClient()->request('GET', '/v1/themes?itemsPerPage=10', ['headers' => ['Content-Type' => 'application/ld+json']]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/Theme',
            '@id' => '/v1/themes',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 20,
            'hydra:view' => [
                '@id' => '/v1/themes?itemsPerPage=10&page=1',
                '@type' => 'hydra:PartialCollectionView',
                'hydra:first' => '/v1/themes?itemsPerPage=10&page=1',
                'hydra:last' => '/v1/themes?itemsPerPage=10&page=2',
                'hydra:next' => '/v1/themes?itemsPerPage=10&page=2',
            ],
        ]);

        $this->assertCount(10, $response->toArray()['hydra:member']);
    }

    public function testGetItem(): void
    {
        $client = static::createClient();
        $iri = $this->findIriBy(Theme::class, []);

        $client->request('GET', $iri, ['headers' => ['Content-Type' => 'application/ld+json']]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => [
                '@vocab' => 'http://example.com/docs.jsonld#',
                'hydra' => 'http://www.w3.org/ns/hydra/core#',
                'title' => 'Theme/title',
                'description' => 'Theme/description',
                'created' => 'Theme/created',
                'modified' => 'Theme/modified',
                'modifiedBy' => 'Theme/modifiedBy',
                'createdBy' => 'Theme/createdBy',
                'css' => 'Theme/css',
            ],
            '@type' => 'Theme',
            '@id' => $iri,
        ]);
    }

    public function testCreateTheme(): void
    {
        $client = static::createClient();

        $response = $client->request('POST', '/v1/themes', [
            'json' => [
                'title' => 'Test theme',
                'description' => 'This is a test theme',
                'modifiedBy' => 'Test Tester',
                'createdBy' => 'Hans Tester',
                'css' => 'body {
                    background-color: #D2691E;
                    color: white;
                    font-family: Montserrat, sans-serif;
                    font-weight:400;
                }',
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
                'title' => 'Theme/title',
                'description' => 'Theme/description',
                'created' => 'Theme/created',
                'modified' => 'Theme/modified',
                'modifiedBy' => 'Theme/modifiedBy',
                'createdBy' => 'Theme/createdBy',
                'css' => 'Theme/css',
            ],
            '@type' => 'Theme',
            'title' => 'Test theme',
            'description' => 'This is a test theme',
            'modifiedBy' => 'Test Tester',
            'createdBy' => 'Hans Tester',
            'css' => 'body {
                    background-color: #D2691E;
                    color: white;
                    font-family: Montserrat, sans-serif;
                    font-weight:400;
                }',
        ]);
        $this->assertMatchesRegularExpression('@^/v\d/\w+/([A-Za-z0-9]{26})$@', $response->toArray()['@id']);
    }

    public function testCreateInvalidTheme(): void
    {
        static::createClient()->request('POST', '/v1/themes', [
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

    public function testUpdateTheme(): void
    {
        $client = static::createClient();
        $iri = $this->findIriBy(Theme::class, []);

        $client->request('PUT', $iri, [
            'json' => [
                'title' => 'Updated title',
                'css' => 'body {
                    background-color: #D2691E;
                    color: blue;
                    font-family: "Comic Sans", sans-serif;
                    font-weight: 200;
                }',
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@type' => 'Theme',
            '@id' => $iri,
            'title' => 'Updated title',
            'css' => 'body {
                    background-color: #D2691E;
                    color: blue;
                    font-family: "Comic Sans", sans-serif;
                    font-weight: 200;
                }',
        ]);
    }

    public function testDeleteTheme(): void
    {
        $client = static::createClient();

        $response = $client->request('POST', '/v1/themes', [
            'json' => [
                'title' => 'Test theme',
                'description' => 'This is a test theme',
                'modifiedBy' => 'Test Tester',
                'createdBy' => 'Hans Tester',
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);

        $iri = $response->toArray()['@id'];
        $client->request('DELETE', $iri);

        $this->assertResponseStatusCodeSame(204);

        $ulid = $this->iriHelperUtils->getUlidFromIRI($iri);
        $this->assertNull(
            static::getContainer()->get('doctrine')->getRepository(Theme::class)->findOneBy(['id' => $ulid])
        );
    }

    public function testDeleteThemeInUse(): void
    {
        $client = static::createClient();

        // Create slide
        $templateIri = $this->findIriBy(Template::class, []);
        $themeIri = $this->findIriBy(Theme::class, []);
        $response = $client->request('POST', '/v1/slides', [
            'json' => [
                'title' => 'Test slide',
                'description' => 'This is a test slide',
                'modifiedBy' => 'Test Tester',
                'createdBy' => 'Hans Tester',
                'templateInfo' => [
                    '@id' => $templateIri,
                    'options' => [
                        'fade' => false,
                    ],
                ],
                'duration' => 60000,
                'published' => [
                    'from' => '2021-09-21T17:00:01.000Z',
                    'to' => '2021-07-22T17:00:01.000Z',
                ],
                'content' => [
                    'text' => 'Test text',
                ],
                'theme' => $themeIri,
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);

        $responseArray = $response->toArray();

        self::assertNotNull($responseArray['theme']);

        $client->request('DELETE', $responseArray['theme']);
        $this->assertResponseStatusCodeSame(204);

        $ulid = $this->iriHelperUtils->getUlidFromIRI($responseArray['theme']);
        $this->assertNull(
            static::getContainer()->get('doctrine')->getRepository(Theme::class)->findOneBy(['id' => $ulid])
        );

        $client->request('GET', $responseArray['@id'], ['headers' => ['Content-Type' => 'application/ld+json']]);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@type' => 'Slide',
            '@id' => $responseArray['@id'],
            'theme' => '',
        ]);
    }
}
