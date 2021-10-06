<?php

namespace App\Tests\Utils;

use ApiPlatform\Core\Exception\InvalidArgumentException;
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

        $dateStr = '2021-09-22T17:00:01Z';
        $date = $this->utils->validateDate($dateStr);

        $this->assertNotEquals(false, $date->diff(new \DateTime($dateStr)));
        $this->assertEquals('2021-09-22T17:00:01Z', $date->format($format));
    }

    public function testInvalidValidateDate(): void
    {
        $format = $_ENV['APP_DEFAULT_DATE_FORMAT'];
        $this->assertNotEmpty($format);

        $dateStr = '2021-09-42T17:00:01Z';
        $this->expectException(InvalidArgumentException::class);
        $this->utils->validateDate($dateStr);
    }
}
