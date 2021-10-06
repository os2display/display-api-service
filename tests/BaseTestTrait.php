<?php

declare(strict_types=1);

namespace App\Tests;

use App\Utils\IriHelperUtils;
use Hautelook\AliceBundle\PhpUnit\BaseDatabaseTrait;

/**
 * @internal
 */
trait BaseTestTrait
{
    use BaseDatabaseTrait;
    private iriHelperUtils $iriHelperUtils;

    public static function setUpBeforeClass(): void
    {
        static::bootKernel();
        static::ensureKernelTestCase();
        static::populateDatabase();
    }

    protected function setUp(): void
    {
        $this->iriHelperUtils = static::getContainer()->get(IriHelperUtils::class);
    }
}
