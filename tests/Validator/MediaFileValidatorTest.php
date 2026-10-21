<?php

declare(strict_types=1);

namespace App\Tests\Validator;

use App\Validator\MediaFile;
use App\Validator\MediaFileValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\ConstraintValidatorFactory;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class MediaFileValidatorTest extends TestCase
{
    /**
     * @dataProvider nonPositiveLimits
     */
    public function testRejectsNonPositiveLimit(int $limit): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/MEDIA_MAX_UPLOAD_SIZE_MB/');

        new MediaFileValidator($limit, 50000000);
    }

    /**
     * @dataProvider nonPositiveLimits
     */
    public function testRejectsNonPositivePixelLimit(int $limit): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/MEDIA_MAX_UPLOAD_PIXELS/');

        new MediaFileValidator(200, $limit);
    }

    public function testPixelLimitCoversEveryAllowedRasterImageType(): void
    {
        // The pixel limit follows MediaFile::$mimeTypes, so an allowed image
        // format is limited without a second list to keep in sync.
        $constraint = new MediaFile();

        $image = new \Imagick();
        $image->newImage(40, 30, 'white');
        $image->setImageFormat('webp');
        $file = stream_get_meta_data(tmpfile())['uri'];
        $image->writeImage($file);

        $validator = Validation::createValidatorBuilder()
            ->setConstraintValidatorFactory(new ConstraintValidatorFactory([
                MediaFileValidator::class => new MediaFileValidator(1, 1000),
            ]))
            ->getValidator();

        $violations = $validator->validate(new File($file), $constraint);

        $this->assertCount(1, $violations);
        $this->assertStringContainsString('megapixels', (string) $violations->get(0)->getMessage());
    }

    public function testPixelLimitMessageGivesDimensionsAndMegapixels(): void
    {
        $image = new \Imagick();
        $image->newImage(1500, 1000, 'white');
        $image->setImageFormat('png');
        $file = stream_get_meta_data(tmpfile())['uri'];
        $image->writeImage($file);

        $violations = $this->validator(1250000)->validate(new File($file), new MediaFile());

        $this->assertCount(1, $violations);
        $this->assertSame(
            'The image is 1500×1000 pixels (1.5 megapixels). The maximum is 1.2 megapixels; scale it down before uploading.',
            $violations->get(0)->getMessage(),
        );
    }

    public function testPixelLimitMessageNeverShowsEqualMegapixels(): void
    {
        // Just over the limit: the image's megapixels round up and the maximum
        // rounds down, so the two never read the same.
        $image = new \Imagick();
        $image->newImage(1001, 1000, 'white');
        $image->setImageFormat('png');
        $file = stream_get_meta_data(tmpfile())['uri'];
        $image->writeImage($file);

        $violations = $this->validator(1000000)->validate(new File($file), new MediaFile());

        $this->assertCount(1, $violations);
        $this->assertStringContainsString(
            '(1.1 megapixels). The maximum is 1 megapixels',
            (string) $violations->get(0)->getMessage(),
        );
    }

    public function testAnimatedGifIsLimitedByThePixelsOfAllFrames(): void
    {
        // Each frame is under the limit, all three together are not: resizing
        // decodes every frame.
        $file = $this->animatedGif(3);

        $violations = $this->validator(25000)->validate(new File($file), new MediaFile());

        $this->assertCount(1, $violations);
        $this->assertSame(
            'The animation has 3 frames of 100×100 pixels (0.1 megapixels in all). The maximum is 0 megapixels; use fewer or smaller frames.',
            $violations->get(0)->getMessage(),
        );
    }

    public function testAnimatedGifWithinTheLimitIsAccepted(): void
    {
        $violations = $this->validator(30000)->validate(new File($this->animatedGif(3)), new MediaFile());

        $this->assertCount(0, $violations);
    }

    public function testImageWithUndetectableSizeIsRejected(): void
    {
        // A PNG header declaring a 0×0 image: sniffed as image/png, but has no
        // usable dimensions.
        $file = stream_get_meta_data(tmpfile())['uri'];
        file_put_contents($file, "\x89PNG\r\n\x1a\n\0\0\0\x0dIHDR".str_repeat("\0", 64));

        $violations = $this->validator(1500000)->validate(new File($file), new MediaFile());

        $this->assertCount(1, $violations);
        $this->assertSame('The size of the image could not be detected.', $violations->get(0)->getMessage());
    }

    private function animatedGif(int $frames): string
    {
        $animation = new \Imagick();
        for ($i = 0; $i < $frames; ++$i) {
            $frame = new \Imagick();
            $frame->newImage(100, 100, 0 === $i % 2 ? 'white' : 'black');
            $frame->setImageFormat('gif');
            $animation->addImage($frame);
        }
        $file = sys_get_temp_dir().'/'.uniqid('animated-', true).'.gif';
        $animation->writeImages($file, true);

        return $file;
    }

    private function validator(int $maxPixels): ValidatorInterface
    {
        return Validation::createValidatorBuilder()
            ->setConstraintValidatorFactory(new ConstraintValidatorFactory([
                MediaFileValidator::class => new MediaFileValidator(1, $maxPixels),
            ]))
            ->getValidator();
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function nonPositiveLimits(): iterable
    {
        yield 'zero' => [0];
        yield 'negative' => [-1];
    }
}
