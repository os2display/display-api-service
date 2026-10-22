<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\State\MediaProvider;
use Liip\ImagineBundle\Binary\BinaryInterface;
use Liip\ImagineBundle\Imagine\Filter\FilterManager;
use Liip\ImagineBundle\Model\Binary;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ScreenImageVariantFilterTest extends KernelTestCase
{
    /**
     * @dataProvider variantSizes
     */
    public function testVariantFitsTheLongEdge(int $size): void
    {
        // Portrait, so the long edge is the height.
        $variant = $this->applyFilter('screen_'.$size, $this->jpeg(1000, 8000));

        $dimensions = getimagesizefromstring($variant->getContent());
        $this->assertNotFalse($dimensions);
        $this->assertSame([(int) round(1000 * $size / 8000), $size], [$dimensions[0], $dimensions[1]]);
    }

    /**
     * @dataProvider variantSizes
     */
    public function testVariantNeverUpscales(int $size): void
    {
        $variant = $this->applyFilter('screen_'.$size, $this->jpeg(800, 600));

        $dimensions = getimagesizefromstring($variant->getContent());
        $this->assertNotFalse($dimensions);
        $this->assertSame([800, 600], [$dimensions[0], $dimensions[1]]);
    }

    /**
     * @dataProvider variantSizes
     */
    public function testListedDimensionsMatchTheGeneratedFile(int $size): void
    {
        // Odd dimensions, so the computed size has to round like Liip does.
        [$width, $height] = [7999, 4321];

        $variant = $this->applyFilter('screen_'.$size, $this->jpeg($width, $height));

        $dimensions = getimagesizefromstring($variant->getContent());
        $this->assertNotFalse($dimensions);
        $this->assertSame(
            [$dimensions[0], $dimensions[1]],
            MediaProvider::screenVariantDimensions($width, $height, $size),
        );
    }

    public function testVariantKeepsTheFormat(): void
    {
        $image = new \Imagick();
        $image->newImage(4000, 3000, 'white');
        $image->setImageFormat('png');

        $variant = $this->applyFilter('screen_1920', new Binary($image->getImageBlob(), 'image/png', 'png'));

        $this->assertSame('image/png', getimagesizefromstring($variant->getContent())['mime'] ?? null);
    }

    public function testPngVariantIsFullyCompressed(): void
    {
        // Imagine turns quality 85 into zlib level 1 for PNG unless the
        // compression level is set.
        $image = new \Imagick();
        $image->newPseudoImage(4000, 3000, 'gradient:red-blue');
        $image->setImageFormat('png');

        $variant = $this->applyFilter('screen_1920', new Binary($image->getImageBlob(), 'image/png', 'png'));

        $level9 = new \Imagick();
        $level9->readImageBlob($variant->getContent());
        $level9->setImageCompressionQuality(95);

        $this->assertLessThanOrEqual(\strlen($level9->getImageBlob()), \strlen($variant->getContent()));
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function variantSizes(): iterable
    {
        foreach (MediaProvider::SCREEN_VARIANT_SIZES as $size) {
            yield (string) $size => [$size];
        }
    }

    private function jpeg(int $width, int $height): Binary
    {
        $image = new \Imagick();
        $image->newImage($width, $height, 'white');
        $image->setImageFormat('jpeg');

        return new Binary($image->getImageBlob(), 'image/jpeg', 'jpg');
    }

    private function applyFilter(string $filter, Binary $binary): BinaryInterface
    {
        self::bootKernel();
        $filterManager = static::getContainer()->get(FilterManager::class);
        \assert($filterManager instanceof FilterManager);

        return $filterManager->applyFilter($binary, $filter);
    }
}
