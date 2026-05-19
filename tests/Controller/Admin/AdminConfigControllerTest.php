<?php

declare(strict_types=1);

namespace App\Tests\Controller\Admin;

use App\Tests\AbstractBaseApiTestCase;
use Symfony\Component\HttpFoundation\Request;

class AdminConfigControllerTest extends AbstractBaseApiTestCase
{
    public function testConfigExposesAllExpectedKeys(): void
    {
        $client = static::createClient();
        $response = $client->request(Request::METHOD_GET, '/config/admin');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $payload = $response->toArray();

        $this->assertArrayHasKey('rejseplanenApiKey', $payload);
        $this->assertArrayHasKey('touchButtonRegions', $payload);
        $this->assertArrayHasKey('showScreenStatus', $payload);
        $this->assertArrayHasKey('loginMethods', $payload);
        $this->assertArrayHasKey('enhancedPreview', $payload);
        $this->assertArrayHasKey('loginScreenText', $payload);
        $this->assertArrayHasKey('mediaMaxUploadSizeMb', $payload);
    }

    public function testMediaMaxUploadSizeMbMatchesConfiguredValue(): void
    {
        $client = static::createClient();
        $response = $client->request(Request::METHOD_GET, '/config/admin');

        $this->assertResponseIsSuccessful();

        $payload = $response->toArray();
        $expected = (int) ($_ENV['MEDIA_MAX_UPLOAD_SIZE_MB'] ?? 200);

        $this->assertIsInt($payload['mediaMaxUploadSizeMb']);
        $this->assertSame($expected, $payload['mediaMaxUploadSizeMb']);
    }
}
