<?php

declare(strict_types=1);

namespace App\Tests\Service;

use Imagine\Image\ImagineInterface;
use Liip\ImagineBundle\Imagine\Filter\FilterManager;
use Liip\ImagineBundle\Model\Binary;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ImageDriverTest extends KernelTestCase
{
    public function testImagesAreProcessedWithImagick(): void
    {
        // gd decodes into PHP memory, where the bundled gd in the production
        // image counts against memory_limit and fails on large uploads.
        // ImageMagick allocates outside it, bounded by its own resource limits.
        self::bootKernel();

        $this->assertInstanceOf(
            \Imagine\Imagick\Imagine::class,
            static::getContainer()->get(ImagineInterface::class),
        );
    }

    public function testThumbnailOfLargeImage(): void
    {
        // 35 megapixels: under the default MEDIA_MAX_UPLOAD_PIXELS.
        $image = new \Imagick();
        $image->newImage(7000, 5000, 'white');
        $image->setImageFormat('jpeg');
        $binary = new Binary($image->getImageBlob(), 'image/jpeg', 'jpg');
        $image->clear();

        self::bootKernel();
        $filterManager = static::getContainer()->get(FilterManager::class);
        \assert($filterManager instanceof FilterManager);

        $thumbnail = $filterManager->applyFilter($binary, 'thumbnail');

        $size = getimagesizefromstring($thumbnail->getContent());
        $this->assertNotFalse($size);
        $this->assertSame([400, 286], [$size[0], $size[1]]);
    }
}
