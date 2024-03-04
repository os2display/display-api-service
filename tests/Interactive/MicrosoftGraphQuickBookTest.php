<?php

declare(strict_types=1);

namespace App\Tests\Interactive;

use App\Interactive\MicrosoftGraphQuickBook;
use Doctrine\ORM\EntityManager;
use Hautelook\AliceBundle\PhpUnit\BaseDatabaseTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MicrosoftGraphQuickBookTest extends KernelTestCase
{
    use BaseDatabaseTrait;

    private EntityManager $entityManager;
    private ContainerInterface $container;

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
        $this->container = static::getContainer();
        $this->entityManager = $this->container->get('doctrine')->getManager();
    }
/*
    public function testGetBookingOptions(): void
    {
        // TODO: Add tests.
        $this->assertEquals(1, 1);
    }

    public function testCreateBooking(): void
    {
        // TODO: Add tests.
        $this->assertEquals(1, 1);
    }
*/
    public function testIntervalFree(): void
    {
        $service = $this->container->get(MicrosoftGraphQuickBook::class);

        $schedules = [
            [
                'startTime' => (new \DateTime())->add(new \DateInterval('PT30M')),
                'endTime' => (new \DateTime())->add(new \DateInterval('PT1H')),
            ]
        ];

        $intervalFree = $service->intervalFree($schedules, new \DateTime(), (new \DateTime())->add(new \DateInterval('PT15M')));
        $this->assertTrue($intervalFree);

        $intervalFree = $service->intervalFree($schedules, (new \DateTime())->add(new \DateInterval('PT15M')), (new \DateTime())->add(new \DateInterval('PT45M')));
        $this->assertFalse($intervalFree);
    }
}
