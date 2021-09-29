<?php

namespace App\Tests;

use ApiPlatform\Core\Exception\InvalidArgumentException;
use App\Utils\Utils;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UtilsTest extends KernelTestCase
{
    private Utils $utils;

    protected function setUp(): void
    {
        $this::bootKernel();
        $this->utils = static::getContainer()->get('App\Utils\Utils');
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

    public function testGetUlidFromIRI(): void
    {
        $this->assertEquals('01FGK86DW4RWJ8JFCJC62ZHX69', $this->utils->getUlidFromIRI('/v1/layouts/01FGK86DW4RWJ8JFCJC62ZHX69'));
        $this->assertEquals('01FGK86DW4RWJ8JFCJC62ZHX5Z', $this->utils->getUlidFromIRI('/v1/layouts/regions/01FGK86DW4RWJ8JFCJC62ZHX5Z'));
    }

    public function testInvalidPathGetUlidFromIRI(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->utils->getUlidFromIRI('/v1/layou2ts/01FGK86DW4RWJ8JFCJC62ZHX69');
    }

    public function testInvalidUlidGetUlidFromIRI(): void
    {
        $this->expectException(InvalidArgumentException::class);

        // First char is bigger than 7.
        $this->utils->getUlidFromIRI('/v1/layouts/81FGK86DW4RWQ8JFCJC62ZHX69');
    }
}
