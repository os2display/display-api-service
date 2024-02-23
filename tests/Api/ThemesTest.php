<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Entity\Tenant\Slide;
use App\Entity\Tenant\Theme;
use App\Tests\AbstractBaseApiTestCase;

class ThemesTest extends AbstractBaseApiTestCase
{
    public function testGetCollection(): void
    {
        $response = $this->getAuthenticatedClient('ROLE_SCREEN')->request('GET', '/v1/themes?itemsPerPage=25', ['headers' => ['Content-Type' => 'application/ld+json']]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/Theme',
            '@id' => '/v1/themes',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 20,
            'hydra:view' => [
                '@id' => '/v1/themes?itemsPerPage=25',
                '@type' => 'hydra:PartialCollectionView',
            ],
        ]);

        $this->assertCount(20, $response->toArray()['hydra:member']);
    }

    public function testGetItem(): void
    {
        $client = $this->getAuthenticatedClient('ROLE_SCREEN');
        $iri = $this->findIriBy(Theme::class, ['tenant' => $this->tenant, 'title' => 'theme_abc_1']);

        $client->request('GET', $iri, ['headers' => ['Content-Type' => 'application/ld+json']]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => [
                '@vocab' => 'http://localhost/docs.jsonld#',
                'hydra' => 'http://www.w3.org/ns/hydra/core#',
                'title' => 'Theme/title',
                'description' => 'Theme/description',
                'created' => 'Theme/created',
                'modified' => 'Theme/modified',
                'modifiedBy' => 'Theme/modifiedBy',
                'createdBy' => 'Theme/createdBy',
                'cssStyles' => 'Theme/css',
            ],
            '@context' => '/contexts/Theme',
            '@type' => 'Theme',
            '@id' => $iri,
            'cssStyles' => ' /* * Example theme file * #SLIDE_ID should always encapsulate all your theme styling * #SLIDE_ID will be replaced at runtime with the given slide execution id to make sure the theme styling * only applies to the given slide. */
#SLIDE_ID { --bg-light: red; --bg-dark: blue; --text-light: purple; --text-dark: green; --text-color: yellow; }
#SLIDE_ID .text { background-color: var(--bg-light); color: var(--text-color); }',
            // FIXME IS this related to `skip_null_values` (https://api-platform.com/docs/core/upgrade-guide/#api-platform-2730)?
            // 'logo' => null,
        ]);
    }

    public function testCreateTheme(): void
    {
        $client = $this->getAuthenticatedClient('ROLE_ADMIN');

        $response = $client->request('POST', '/v1/themes', [
            'json' => [
                'title' => 'Test theme',
                'description' => 'This is a test theme',
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
            '@context' => '/contexts/Theme',
            '@type' => 'Theme',
            'title' => 'Test theme',
            'description' => 'This is a test theme',
            'modifiedBy' => 'test@example.com',
            'createdBy' => 'test@example.com',
            'cssStyles' => 'body {
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
        $this->getAuthenticatedClient('ROLE_ADMIN')->request('POST', '/v1/themes', [
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

    public function testUpdateTheme(): void
    {
        $client = $this->getAuthenticatedClient('ROLE_ADMIN');
        $iri = $this->findIriBy(Theme::class, ['tenant' => $this->tenant, 'title' => 'theme_abc_1']);

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
            'cssStyles' => 'body {
                    background-color: #D2691E;
                    color: blue;
                    font-family: "Comic Sans", sans-serif;
                    font-weight: 200;
                }',
        ]);
    }

    public function testDeleteTheme(): void
    {
        $client = $this->getAuthenticatedClient('ROLE_ADMIN');

        $response = $client->request('POST', '/v1/themes', [
            'json' => [
                'title' => 'Test theme',
                'description' => 'This is a test theme',
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);
        $this->assertResponseIsSuccessful();

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
        $client = $this->getAuthenticatedClient('ROLE_ADMIN');

        $qb = static::getContainer()->get('doctrine')->getRepository(Slide::class)->createQueryBuilder('s');

        /** @var Slide $slide */
        $slide = $qb
            ->leftJoin('s.theme', 'theme')->addSelect('theme')
            ->leftJoin('s.tenant', 'tenant')->addSelect('tenant')
            ->where('s.theme IS NOT NULL')
            ->andWhere('tenant.tenantKey = :tenantKey')
            ->setParameter('tenantKey', 'ABC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
        $theme = $slide->getTheme();

        $themeId = $theme->getId();

        $themeIri = $this->findIriBy(Theme::class, ['id' => $themeId]);

        $client->request('DELETE', $themeIri);

        $this->assertResponseStatusCodeSame(409);

        $ulid = $this->iriHelperUtils->getUlidFromIRI($themeIri);

        $this->assertNotNull(
            static::getContainer()->get('doctrine')->getRepository(Theme::class)->findOneBy(['id' => $ulid])
        );
    }
}
