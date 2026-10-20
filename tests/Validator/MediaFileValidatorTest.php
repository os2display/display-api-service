<?php

declare(strict_types=1);

namespace App\Tests\Validator;

use App\Validator\MediaFileValidator;
use PHPUnit\Framework\TestCase;

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

    /**
     * @return iterable<string, array{int}>
     */
    public static function nonPositiveLimits(): iterable
    {
        yield 'zero' => [0];
        yield 'negative' => [-1];
    }
}
