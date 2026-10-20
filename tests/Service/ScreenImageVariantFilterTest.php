<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\State\MediaProvider;
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

    public function testVariantKeepsTheFormat(): void
    {
        $image = new \Imagick();
        $image->newImage(4000, 3000, 'white');
        $image->setImageFormat('png');

        $variant = $this->applyFilter('screen_1920', new Binary($image->getImageBlob(), 'image/png', 'png'));

        $this->assertSame('image/png', getimagesizefromstring($variant->getContent())['mime'] ?? null);
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

    private function applyFilter(string $filter, Binary $binary): \Liip\ImagineBundle\Binary\BinaryInterface
    {
        self::bootKernel();
        $filterManager = static::getContainer()->get(FilterManager::class);
        \assert($filterManager instanceof FilterManager);

        return $filterManager->applyFilter($binary, $filter);
    }
}
