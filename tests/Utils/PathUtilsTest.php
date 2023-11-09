<?php

namespace App\Tests\Utils;

use App\Utils\PathUtils;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PathUtilsTest extends KernelTestCase
{
    protected function setUp(): void
    {
        $this::bootKernel();
    }

    public function testPathPrefix(): void
    {
        $utils = new PathUtils(['route_prefix' => 'v1']);
        $this->assertEquals('/v1/', $utils->getApiPlatformPathPrefix());

        $utils = new PathUtils(['route_prefix' => '/v1']);
        $this->assertEquals('/v1/', $utils->getApiPlatformPathPrefix());

        $utils = new PathUtils(['route_prefix' => '/v1/test']);
        $this->assertEquals('/v1/test/', $utils->getApiPlatformPathPrefix());

        $utils = new PathUtils([]);
        $this->assertEquals('/', $utils->getApiPlatformPathPrefix());
    }
}
