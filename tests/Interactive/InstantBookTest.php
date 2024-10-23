<?php

declare(strict_types=1);

namespace App\Tests\Interactive;

use App\InteractiveSlide\InstantBook;
use Doctrine\ORM\EntityManager;
use Hautelook\AliceBundle\PhpUnit\BaseDatabaseTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class InstantBookTest extends KernelTestCase
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

    public function testIntervalFree(): void
    {
        $service = $this->container->get(InstantBook::class);

        $schedules = [
            [
                'startTime' => new \DateTime('+30 minutes'),
                'endTime' => (new \DateTime('+1 hour')),
            ],
        ];

        $intervalFree = $service->intervalFree($schedules, new \DateTime(), new \DateTime('+15 minutes'));
        $this->assertTrue($intervalFree);

        $intervalFree = $service->intervalFree($schedules, new \DateTime('+15 minutes'), new \DateTime('+45 minutes'));
        $this->assertFalse($intervalFree);
    }
}
