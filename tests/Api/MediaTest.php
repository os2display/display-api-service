<?php

declare(strict_types=1);

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Entity\Tenant;
use App\Entity\Tenant\Media;
use App\Entity\Tenant\Theme;
use App\Tests\AbstractBaseApiTestCase;
use App\Tests\Probe\CalculatorProbe;
use Liip\ImagineBundle\Model\Binary;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Vich\UploaderBundle\Storage\StorageInterface;

class MediaTest extends AbstractBaseApiTestCase
{
    public function testGetCollection(): void
    {
        $response = $this->getAuthenticatedClient()->request('GET', '/v2/media?itemsPerPage=10', ['headers' => ['Content-Type' => 'application/ld+json']]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/Media',
            '@id' => '/v2/media',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 100,
            'hydra:view' => [
                '@id' => '/v2/media?itemsPerPage=10&page=1',
                '@type' => 'hydra:PartialCollectionView',
                'hydra:first' => '/v2/media?itemsPerPage=10&page=1',
                'hydra:last' => '/v2/media?itemsPerPage=10&page=10',
                'hydra:next' => '/v2/media?itemsPerPage=10&page=2',
            ],
        ]);

        $this->assertCount(10, $response->toArray()['hydra:member']);

        // @TODO: hydra:member[0].assets: Object value found, but an array is required
        // $this->assertMatchesResourceCollectionJsonSchema(Media::class);
    }

    public function testGetItem(): void
    {
        $client = $this->getAuthenticatedClient();
        $manager = static::getContainer()->get('doctrine')->getManager();

        // Check visibility between tenants - One Tenant should not see media from another tenant
        $tenantXyz = $manager->getRepository(Tenant::class)->findOneBy(['tenantKey' => 'XYZ']);
        $iriXyz = $this->findIriBy(Media::class, ['tenant' => $tenantXyz]);

        $client->request('GET', $iriXyz, ['headers' => ['Content-Type' => 'application/ld+json']]);
        $this->assertResponseStatusCodeSame(404, 'One Tenant should not see media from another tenant');

        // Tenant should see own media
        $iri = $this->findIriBy(Media::class, ['tenant' => $this->tenant]);

        $client->request('GET', $iri, ['headers' => ['Content-Type' => 'application/ld+json']]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => [
                '@vocab' => 'http://localhost/docs.jsonld#',
                'hydra' => 'http://www.w3.org/ns/hydra/core#',
                'title' => 'Media/title',
                'description' => 'Media/description',
                'license' => 'Media/license',
                'created' => 'Media/created',
                'modified' => 'Media/modified',
                'modifiedBy' => 'Media/modifiedBy',
                'createdBy' => 'Media/createdBy',
                'media' => 'Media/media',
                'assets' => 'Media/assets',
            ],
            '@type' => 'Media',
            '@id' => $iri,
        ]);

        // @TODO: hydra:member[0].assets: Object value found, but an array is required
        //        $this->assertMatchesResourceItemJsonSchema(Media::class);
    }

    public function testMediaUrlFromForeignTenant(): void
    {
        $iri = $this->findIriBy(Media::class, ['title' => 'media_def_shared_to_abc']);

        $response = $this->getAuthenticatedClient()->request('GET', $iri, ['headers' => ['Content-Type' => 'application/ld+json']]);

        $this->assertResponseIsSuccessful();
        $responseArray = $response->toArray();
        $this->assertStringContainsString(
            '/media/def/',
            $responseArray['assets']['uri'],
            "Media belonging to 'DEF' should be in a sub folder '/def'"
        );
    }

    public function testMediaUpload(): void
    {
        // Move test file content into tmp file as the file upload will remove the source file. WTF.
        $tmpFile = stream_get_meta_data(tmpfile())['uri'];
        file_put_contents($tmpFile, file_get_contents('fixtures/files/test.jpg'));
        $file = new UploadedFile($tmpFile, 'test.jpg');

        $client = $this->getAuthenticatedClient();
        $calculatorProbe = static::getContainer()->get(CalculatorProbe::class);
        $calculatorProbe->reset();
        $response = $client->request('POST', '/v2/media', [
            'extra' => [
                'parameters' => [
                    'title' => 'Test media',
                    'description' => 'This is a test media upload',
                    'license' => 'Free CC',
                ],
                'files' => [
                    'file' => $file,
                ],
            ],
            'headers' => [
                'Content-Type' => 'multipart/form-data',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        // One flush, so one calculator run outside a transaction.
        $this->assertSame(1, $calculatorProbe->invocations());
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => [
                '@vocab' => 'http://localhost/docs.jsonld#',
                'hydra' => 'http://www.w3.org/ns/hydra/core#',
                'title' => 'Media/title',
                'description' => 'Media/description',
                'license' => 'Media/license',
                'created' => 'Media/created',
                'modified' => 'Media/modified',
                'modifiedBy' => 'Media/modifiedBy',
                'createdBy' => 'Media/createdBy',
                'media' => 'Media/media',
                'assets' => 'Media/assets',
            ],
            '@type' => 'Media',
            'title' => 'Test media',
            'description' => 'This is a test media upload',
            'license' => 'Free CC',
            'modifiedBy' => 'test@example.com',
            'createdBy' => 'test@example.com',
            'media' => [],
            'assets' => [
                'type' => 'image/jpeg',
                'dimensions' => [
                    'height' => 800,
                    'width' => 1280,
                ],
                'sha' => '6bad886ed9f0ed053dc0ec69d5fa79a25cfbfccb',
                'size' => 367965,
            ],
        ]);

        $responseArray = $response->toArray();
        $this->assertMatchesRegularExpression('@^/v\d/\w+/([A-Za-z0-9]{26})$@', $responseArray['@id']);
        $this->assertStringContainsString(
            '/media/abc/',
            $responseArray['assets']['uri'],
            'Media should be uploaded to a sub folder named by tenant key'
        );

        // @TODO: hydra:member[0].assets: Object value found, but an array is required
        //        $this->assertMatchesResourceItemJsonSchema(Media::class);
    }

    public function testMediaUploadAcceptsWebp(): void
    {
        $image = new \Imagick();
        $image->newImage(1280, 800, 'white');
        $image->setImageFormat('webp');
        $tmpFile = stream_get_meta_data(tmpfile())['uri'];
        $image->writeImage($tmpFile);

        $file = new UploadedFile($tmpFile, 'test.webp');

        $this->getAuthenticatedClient()->request('POST', '/v2/media', [
            'extra' => [
                'parameters' => [
                    'title' => 'WebP media',
                    'description' => 'A WebP upload',
                    'license' => 'Free CC',
                ],
                'files' => [
                    'file' => $file,
                ],
            ],
            'headers' => [
                'Content-Type' => 'multipart/form-data',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'assets' => [
                'type' => 'image/webp',
                'dimensions' => [
                    'height' => 800,
                    'width' => 1280,
                ],
            ],
        ]);
    }

    public function testSvgThumbnailIsTheOriginal(): void
    {
        // SVGs never go through ImageMagick: an SVG can reference files and
        // URLs that its readers would fetch. Browsers render SVG in <img>
        // without scripts or external loads.
        $tmpFile = stream_get_meta_data(tmpfile())['uri'];
        file_put_contents($tmpFile, '<?xml version="1.0"?><svg xmlns="http://www.w3.org/2000/svg" width="40" height="30"><rect width="40" height="30"/></svg>');

        $file = new UploadedFile($tmpFile, 'test.svg');

        $response = $this->getAuthenticatedClient()->request('POST', '/v2/media', [
            'extra' => [
                'parameters' => [
                    'title' => 'SVG media',
                    'description' => 'An SVG upload',
                    'license' => 'Free CC',
                ],
                'files' => [
                    'file' => $file,
                ],
            ],
            'headers' => [
                'Content-Type' => 'multipart/form-data',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $media = $response->toArray();
        $this->assertSame('image/svg+xml', $media['assets']['type']);
        $this->assertSame($media['assets']['uri'], $media['thumbnail']);
    }

    public function testImageListsScreenVariantsSmallerThanTheOriginal(): void
    {
        // Under the test env's 2 MP upload limit, with a long edge between the
        // 1920 and 3840 variants. 799 × 1920/2401 = 638.9, rounded like Liip.
        $media = $this->uploadImage(2401, 799, 'jpeg');

        $this->assertSame(['1920'], array_map(strval(...), array_keys($media['assets']['variants'])));
        $variant = $media['assets']['variants']['1920'];
        $this->assertSame(1920, $variant['width']);
        $this->assertSame(639, $variant['height']);
        $this->assertStringContainsString('/media/cache/resolve/screen_1920/', $variant['uri']);
    }

    public function testSmallImageHasNoScreenVariants(): void
    {
        $media = $this->uploadImage(1920, 1000, 'png');

        $this->assertArrayNotHasKey('variants', $media['assets']);
    }

    public function testImageWithAnUnknownDimensionHasNoScreenVariants(): void
    {
        // Variant dimensions divide by the original's; a row with one of them
        // unknown (0) must not fail the whole response.
        $media = $this->uploadImage(2401, 799, 'jpeg');

        $manager = static::getContainer()->get('doctrine')->getManager();
        $entity = $manager->getRepository(Media::class)->find(basename((string) $media['@id']));
        $this->assertInstanceOf(Media::class, $entity);
        $entity->setHeight(0);
        $manager->flush();

        $response = $this->getAuthenticatedClient()->request('GET', $media['@id']);

        $this->assertResponseIsSuccessful();
        $this->assertArrayNotHasKey('variants', $response->toArray()['assets']);
    }

    public function testGifHasNoScreenVariants(): void
    {
        // Animated GIF frames are not resized reliably; screens get the original.
        $media = $this->uploadImage(2400, 800, 'gif');

        $this->assertArrayNotHasKey('variants', $media['assets']);
    }

    /**
     * @return array<string, mixed>
     */
    private function uploadImage(int $width, int $height, string $format): array
    {
        $image = new \Imagick();
        $image->newImage($width, $height, 'white');
        $image->setImageFormat($format);
        $tmpFile = stream_get_meta_data(tmpfile())['uri'];
        $image->writeImage($tmpFile);

        $response = $this->getAuthenticatedClient()->request('POST', '/v2/media', [
            'extra' => [
                'parameters' => [
                    'title' => 'Image media',
                    'description' => 'An image upload',
                    'license' => 'Free CC',
                ],
                'files' => [
                    'file' => new UploadedFile($tmpFile, 'test.'.$format),
                ],
            ],
            'headers' => [
                'Content-Type' => 'multipart/form-data',
            ],
        ]);

        $this->assertResponseIsSuccessful();

        return $response->toArray();
    }

    public function testMediaUploadNonImage(): void
    {
        $content = '<?xml version="1.0" encoding="UTF-8"?><svg xmlns="http://www.w3.org/2000/svg" width="10" height="10"></svg>';
        $tmpFile = stream_get_meta_data(tmpfile())['uri'];
        file_put_contents($tmpFile, $content);
        $file = new UploadedFile($tmpFile, 'test.svg');

        $this->getAuthenticatedClient()->request('POST', '/v2/media', [
            'extra' => [
                'parameters' => [
                    'title' => 'Test svg',
                    'description' => 'This is a test svg upload',
                    'license' => 'Free CC',
                ],
                'files' => [
                    'file' => $file,
                ],
            ],
            'headers' => [
                'Content-Type' => 'multipart/form-data',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        // getimagesize() does not read SVG, so dimensions stay 0.
        $this->assertJsonContains([
            'assets' => [
                'type' => 'image/svg+xml',
                'dimensions' => [
                    'height' => 0,
                    'width' => 0,
                ],
                'sha' => sha1($content),
                'size' => strlen($content),
            ],
        ]);
    }

    public function testMediaUploadRejectedWhenTooLarge(): void
    {
        // Driven by the MEDIA_MAX_UPLOAD_SIZE_MB env var via the `#[MediaFile]`
        // attribute on `Media::$file`. .env.test should set this to a small value
        // (e.g. 1) so we can build the over-limit file cheaply. If the configured
        // limit is too large to test cheaply, skip.
        $maxSizeMb = (int) ($_ENV['MEDIA_MAX_UPLOAD_SIZE_MB'] ?? 200);
        if ($maxSizeMb > 10) {
            $this->markTestSkipped(sprintf(
                'MEDIA_MAX_UPLOAD_SIZE_MB=%d is too large to test cheaply; set it to 1 in .env.test to enable this test.',
                $maxSizeMb,
            ));
        }

        $tmpFile = stream_get_meta_data(tmpfile())['uri'];
        // Start with a real JPG so the mime check would pass; pad until the file exceeds the limit.
        file_put_contents($tmpFile, file_get_contents('fixtures/files/test.jpg'));
        $targetSize = ($maxSizeMb * 1024 * 1024) + 1024;
        $fh = fopen($tmpFile, 'a');
        fwrite($fh, str_repeat("\0", $targetSize - filesize($tmpFile)));
        fclose($fh);

        $file = new UploadedFile($tmpFile, 'too-large.jpg');

        $this->getAuthenticatedClient()->request('POST', '/v2/media', [
            'extra' => [
                'parameters' => [
                    'title' => 'Too large',
                    'description' => 'Exceeds configured max upload size',
                    'license' => 'Free CC',
                ],
                'files' => [
                    'file' => $file,
                ],
            ],
            'headers' => [
                'Content-Type' => 'multipart/form-data',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testMediaUploadRejectedWhenTooManyPixels(): void
    {
        // Driven by MEDIA_MAX_UPLOAD_PIXELS, lowered in .env.test so a small,
        // well-compressed image can exceed it.
        $maxPixels = (int) ($_ENV['MEDIA_MAX_UPLOAD_PIXELS'] ?? 50000000);
        if ($maxPixels > 4000000) {
            $this->markTestSkipped(sprintf(
                'MEDIA_MAX_UPLOAD_PIXELS=%d is too large to test cheaply; set it to 2000000 in .env.test to enable this test.',
                $maxPixels,
            ));
        }

        $width = 2000;
        $height = intdiv($maxPixels, $width) + 1;
        $tmpFile = stream_get_meta_data(tmpfile())['uri'];
        $image = new \Imagick();
        $image->newImage($width, $height, 'black');
        $image->setImageFormat('jpeg');
        $image->writeImage($tmpFile);

        $file = new UploadedFile($tmpFile, 'too-many-pixels.jpg');

        $this->getAuthenticatedClient()->request('POST', '/v2/media', [
            'extra' => [
                'parameters' => [
                    'title' => 'Too many pixels',
                    'description' => 'Exceeds configured max upload pixels',
                    'license' => 'Free CC',
                ],
                'files' => [
                    'file' => $file,
                ],
            ],
            'headers' => [
                'Content-Type' => 'multipart/form-data',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testDeleteMedia(): void
    {
        [$client, $media, $path] = $this->mediaWithCachedThumbnail(onSlides: false);
        $id = $media->getId();

        $client->request('DELETE', '/v2/media/'.$id);

        $this->assertResponseStatusCodeSame(204);
        $this->assertNull(static::getContainer()->get('doctrine')->getRepository(Media::class)->find($id));
        $this->assertFalse(static::getContainer()->get('liip_imagine.cache.manager')->isStored($path, 'thumbnail'));
    }

    public function testDeleteMediaOnSlidesIsRefused(): void
    {
        [$client, $media, $path] = $this->mediaWithCachedThumbnail(onSlides: true);

        $client->request('DELETE', '/v2/media/'.$media->getId());

        $this->assertResponseStatusCodeSame(409);
        $this->assertTrue(static::getContainer()->get('liip_imagine.cache.manager')->isStored($path, 'thumbnail'));
    }

    public function testFailedDeleteKeepsCachedImages(): void
    {
        [$client, $media, $path] = $this->mediaWithCachedThumbnail(onSlides: false);

        // A theme's logo: the theme.logo_id foreign key fails the delete in
        // the flush, after preRemove (#568).
        $manager = static::getContainer()->get('doctrine')->getManager();
        $theme = $manager->getRepository(Theme::class)->findOneBy(['tenant' => $this->tenant]);
        $theme->setLogo($media);
        $manager->flush();

        $client->request('DELETE', '/v2/media/'.$media->getId());

        $this->assertResponseStatusCodeSame(500);
        $this->assertTrue(static::getContainer()->get('liip_imagine.cache.manager')->isStored($path, 'thumbnail'));
    }

    /**
     * A fixture media, on slides or not, with a thumbnail in the image cache.
     *
     * The client keeps the kernel, so the media and listeners set up here are
     * the ones the request sees.
     *
     * @return array{Client, Media, string} The client, the media and its path in the image cache
     */
    private function mediaWithCachedThumbnail(bool $onSlides): array
    {
        $client = $this->getAuthenticatedClient();
        $client->disableReboot();

        $medias = static::getContainer()->get('doctrine')->getRepository(Media::class)->findBy(['tenant' => $this->tenant]);
        $media = array_values(array_filter($medias, fn (Media $media) => $onSlides === count($media->getSlides()) > 0))[0];

        $path = static::getContainer()->get(StorageInterface::class)->resolveUri($media, 'file');
        $cacheManager = static::getContainer()->get('liip_imagine.cache.manager');
        $cacheManager->store(new Binary(file_get_contents('fixtures/files/test.jpg'), 'image/jpeg', 'jpg'), $path, 'thumbnail');
        $this->assertTrue($cacheManager->isStored($path, 'thumbnail'));

        return [$client, $media, $path];
    }
}
