<?php

namespace App\Tests\Utils;

use ApiPlatform\Core\Exception\InvalidArgumentException;
use App\Utils\IriHelperUtils;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class IriHelperUtilsTest extends KernelTestCase
{
    private IriHelperUtils $utils;

    protected function setUp(): void
    {
        $this::bootKernel();
        $this->utils = static::getContainer()->get(IriHelperUtils::class);
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
