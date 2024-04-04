<?php

declare(strict_types=1);

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
        $utils = new PathUtils(['route_prefix' => 'v2']);
        $this->assertEquals('/v2/', $utils->getApiPlatformPathPrefix());

        $utils = new PathUtils(['route_prefix' => '/v2']);
        $this->assertEquals('/v2/', $utils->getApiPlatformPathPrefix());

        $utils = new PathUtils(['route_prefix' => '/v2/test']);
        $this->assertEquals('/v2/test/', $utils->getApiPlatformPathPrefix());

        $utils = new PathUtils([]);
        $this->assertEquals('/', $utils->getApiPlatformPathPrefix());
    }
}
