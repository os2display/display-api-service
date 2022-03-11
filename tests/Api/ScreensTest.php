<?php

namespace App\Tests\Api;

use App\Entity\Tenant\Screen;
use App\Entity\Tenant\ScreenLayout;
use App\Tests\AbstractBaseApiTestCase;
use Symfony\Component\BrowserKit\Response;
use Symfony\Component\HttpFoundation\Cookie;

class ScreensTest extends AbstractBaseApiTestCase
{
    public function testGetCollection(): void
    {
        $response = $this->getAuthenticatedClient('ROLE_ADMIN')->request('GET', '/v1/screens?itemsPerPage=5', ['headers' => ['Content-Type' => 'application/ld+json']]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/Screen',
            '@id' => '/v1/screens',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 100,
            'hydra:view' => [
                '@id' => '/v1/screens?itemsPerPage=5&page=1',
                '@type' => 'hydra:PartialCollectionView',
                'hydra:first' => '/v1/screens?itemsPerPage=5&page=1',
                'hydra:last' => '/v1/screens?itemsPerPage=5&page=20',
                'hydra:next' => '/v1/screens?itemsPerPage=5&page=2',
            ],
        ]);

        $this->assertCount(5, $response->toArray()['hydra:member']);

        // @TODO: hydra:member[0].dimensions: Object value found, but an array is required
//        $this->assertMatchesResourceCollectionJsonSchema(Screen::class);
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
                '@vocab' => 'http://example.com/docs.jsonld#',
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
                'dimensions' => 'Screen/dimensions',
            ],
            '@type' => 'Screen',
            '@id' => $iri,
        ]);
    }

    public function testCreateScreen(): void
    {
        $client = $this->getAuthenticatedClient('ROLE_ADMIN');

        $layoutIri = $this->findIriBy(ScreenLayout::class, ['tenant' => $this->tenant]);

        $response = $client->request('POST', '/v1/screens', [
            'json' => [
                'title' => 'Test screen 42',
                'description' => 'This is a test screen',
                'size' => '65',
                'modifiedBy' => 'Test Tester',
                'createdBy' => 'Test Hansen',
                'layout' => $layoutIri,
                'location' => 'M2.42',
                'dimensions' => [
                    'width' => 1920,
                    'height' => 1080,
                ],
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
                'dimensions' => 'Screen/dimensions',
            ],
            '@type' => 'Screen',
            'title' => 'Test screen 42',
            'description' => 'This is a test screen',
            'size' => '65',
            'modifiedBy' => 'Test Tester',
            'createdBy' => 'Test Hansen',
            'layout' => $layoutIri,
            'location' => 'M2.42',
            'dimensions' => [
                'width' => 1920,
                'height' => 1080,
            ],
        ]);
        $this->assertMatchesRegularExpression('@^/v\d/\w+/([A-Za-z0-9]{26})$@', $response->toArray()['@id']);

        // @TODO: dimensions: Object value found, but an array is required
//        $this->assertMatchesResourceItemJsonSchema(Screen::class);
    }

    public function testCreateInvalidScreen(): void
    {
        $this->getAuthenticatedClient('ROLE_ADMIN')->request('POST', '/v1/screens', [
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

    // TODO: Fix issue with session for this test.
    //    public function testBindFlowScreen(): void
    //    {
    //        $authenticatedClient = $this->getAuthenticatedClient();
    //        $screenIri = $this->findIriBy(Screen::class, ['tenant' => $this->tenant]);
    //
    //        /** @var Response $resp1 */
    //        $resp1 = $authenticatedClient->request('POST', '/v1/authentication/screen', [
    //            'headers' => [
    //                'Content-Type' => 'application/ld+json',
    //            ],
    //        ]);
    //
    //        $contents = json_decode($resp1->getContent());
    //
    //        $setCookie = $resp1->getHeaders()['set-cookie'];
    //
    //        $this->assertSame(200, $resp1->getStatusCode());
    //        $this->assertNotEmpty($contents->bindKey);
    //
    //        $this->assertEquals(8, strlen($contents->bindKey));
    //
    //        // Bind screen.
    //        $authenticatedClient->request('POST', $screenIri.'/bind', [
    //            'json' => [
    //                'bindKey' => $contents->bindKey,
    //            ],
    //            'headers' => [
    //                'Content-Type' => 'application/ld+json',
    //            ],
    //        ]);
    //
    //        $this->assertResponseStatusCodeSame(201, 'Response status code should be 201');
    //
    //        $authenticatedClient->getCookieJar()->updateFromSetCookie($setCookie);
    //
    //        /** @var Response $resp3 */
    //        $resp3 = $authenticatedClient->request('POST', '/v1/authentication/screen', [
    //            'headers' => [
    //                'Content-Type' => 'application/ld+json',
    //            ],
    //        ]);
    //
    //        $this->assertResponseIsSuccessful();
    //        $this->assertJsonContains([
    //            'status' => 'ready',
    //        ]);
    //
    //        $resp3Content = json_decode($resp3->getContent());
    //
    //        $this->assertNotEmpty($resp3Content['token']);
    //
    //        // Unbind screen.
    //        $authenticatedClient->request('POST', $screenIri.'/unbind', [
    //            'headers' => [
    //                'Content-Type' => 'application/ld+json',
    //            ],
    //        ]);
    //    }
}
