<?php

declare(strict_types=1);

namespace App\Tests\Interactive;

use Doctrine\ORM\EntityManager;
use Hautelook\AliceBundle\PhpUnit\BaseDatabaseTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class MicrosoftGraphQuickBookTest extends KernelTestCase
{
    use BaseDatabaseTrait;

    private EntityManager $entityManager;

    public static function setUpBeforeClass(): void
    {
        static::bootKernel();
        static::ensureKernelTestCase();
        if (!filter_var(getenv('API_TEST_CASE_DO_NOT_POPULATE_DATABASE'), FILTER_VALIDATE_BOOL)) {
            static::populateDatabase();
        }
    }

    public function setUp(): void
    {
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();
    }

    public function testGetBookingOptions(): void
    {
        $this->assertEquals(1, 1);
    }

    public function testCreateBooking(): void
    {
        $this->assertEquals(1, 1);
    }
}
