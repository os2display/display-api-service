<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Entity\Template;
use App\Entity\Tenant\FeedSource;
use App\Entity\Tenant\Slide;
use App\Entity\Tenant\Theme;
use App\Tests\AbstractBaseApiTestCase;

class SetTenantTest extends AbstractBaseApiTestCase
{
    public function testTenantSetOnCreate(): void
    {
        $client = $this->getAuthenticatedClient();
        $templateIri = $this->findIriBy(Template::class, []);
        $themeIri = $this->findIriBy(Theme::class, ['tenant' => $this->tenant]);
        $feedSource = $this->findIriBy(FeedSource::class, ['tenant' => $this->tenant]);

        $response = $client->request('POST', '/v1/slides', [
            'json' => [
                'title' => 'Test slide',
                'description' => 'This is a test slide',
                'templateInfo' => [
                    '@id' => $templateIri,
                    'options' => [
                        'fade' => false,
                    ],
                ],
                'theme' => $themeIri,
                'duration' => 60000,
                'published' => [
                    'from' => '2021-09-21T17:00:01.000Z',
                    'to' => '2021-07-22T17:00:01.000Z',
                ],
                'feed' => [
                    'feedSource' => $feedSource,
                    'configuration' => [
                        'key1' => 'value1',
                    ],
                ],
                'content' => [
                    'text' => 'Test text',
                ],
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);

        $id = $response->toArray()['@id'];
        $ulid = $this->iriHelperUtils->getUlidFromIRI($id);
        $slide = static::getContainer()->get('doctrine')->getRepository(Slide::class)->findOneBy(['id' => $ulid]);
        $this->assertEquals('ABC', $slide->getTenant()->getTenantKey());
    }
}
