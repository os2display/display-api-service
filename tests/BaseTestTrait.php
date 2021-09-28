<?php

declare(strict_types=1);

namespace App\Tests;

use App\Utils\Utils;
use Hautelook\AliceBundle\PhpUnit\BaseDatabaseTrait;

trait BaseTestTrait
{
    use BaseDatabaseTrait;
    private Utils $utils;

    public static function setUpBeforeClass(): void
    {
        static::bootKernel();
        static::ensureKernelTestCase();
        static::populateDatabase();
    }

    protected function setUp(): void
    {
        $this->utils = static::getContainer()->get('App\Utils\Utils');
    }
}
