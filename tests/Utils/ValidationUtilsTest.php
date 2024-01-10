<?php

declare(strict_types=1);

namespace App\Tests\Utils;

use ApiPlatform\Exception\InvalidArgumentException;
use App\Utils\IriHelperUtils;
use App\Utils\ValidationUtils;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ValidationUtilsTest extends KernelTestCase
{
    private ValidationUtils $utils;

    protected function setUp(): void
    {
        $this::bootKernel();
        $this->utils = static::getContainer()->get(ValidationUtils::class);
        $t = static::getContainer()->get(IriHelperUtils::class);
    }

    public function testValidateDate(): void
    {
        $format = $_ENV['APP_DEFAULT_DATE_FORMAT'];
        $this->assertNotEmpty($format);

        $dateStr = '2021-09-22T17:00:01.000Z';
        $date = $this->utils->validateDate($dateStr);

        $this->assertNotEquals(false, $date->diff(new \DateTime($dateStr)));
        $this->assertEquals('2021-09-22T17:00:01.000Z', $date->format($format));
    }

    public function testInvalidValidateDate(): void
    {
        $format = $_ENV['APP_DEFAULT_DATE_FORMAT'];
        $this->assertNotEmpty($format);

        $dateStr = '2021-09-42T17:00:01Z';
        $this->expectException(InvalidArgumentException::class);
        $this->utils->validateDate($dateStr);
    }

    public function testValidateUlid(): void
    {
        $str = '01FHB9MFEQV7SC2KQWB14PVY3K';
        $ulid = $this->utils->validateUlid($str);

        $this->assertEquals($str, $ulid->toBase32());
    }

    public function testValidateUlidException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $str = '01QTestInValidUlidB14PVY3K';
        $this->utils->validateUlid($str);
    }
}
