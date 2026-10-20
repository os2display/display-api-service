<?php

declare(strict_types=1);

namespace App\Tests\Validator;

use App\Validator\MediaFile;
use App\Validator\MediaFileValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\ConstraintValidatorFactory;
use Symfony\Component\Validator\Validation;

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
        $this->assertStringContainsString('too many pixels', (string) $violations->get(0)->getMessage());
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
