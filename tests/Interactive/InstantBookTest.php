<?php

declare(strict_types=1);

namespace App\Tests\Interactive;

use App\Entity\Tenant\Feed;
use App\Entity\Tenant\FeedSource;
use App\Exceptions\NotAcceptableException;
use App\Feed\FeedOutputModels;
use App\Feed\FeedTypeInterface;
use App\InteractiveSlide\InstantBook;
use App\Service\FeedService;
use App\Service\InteractiveSlideService;
use App\Service\KeyVaultService;
use Doctrine\ORM\EntityManager;
use Hautelook\AliceBundle\PhpUnit\BaseDatabaseTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

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

    public function testGetBusyIntervalsFromFeedFiltersByResourceAndWindow(): void
    {
        $from = new \DateTime('2026-01-01T10:00:00', new \DateTimeZone('UTC'));
        $to = new \DateTime('2026-01-01T11:00:00', new \DateTimeZone('UTC'));

        $events = [
            // In-window event for resource A.
            ['resourceId' => 'a@example.com', 'startTime' => $from->getTimestamp() + 600,  'endTime' => $from->getTimestamp() + 1800],
            // In-window event for resource B.
            ['resourceId' => 'b@example.com', 'startTime' => $from->getTimestamp() + 1200, 'endTime' => $from->getTimestamp() + 2400],
            // Out-of-window event (entirely before from).
            ['resourceId' => 'a@example.com', 'startTime' => $from->getTimestamp() - 7200, 'endTime' => $from->getTimestamp() - 3600],
            // Event for an unwatched resource.
            ['resourceId' => 'c@example.com', 'startTime' => $from->getTimestamp() + 600,  'endTime' => $from->getTimestamp() + 1800],
        ];

        $instantBook = $this->buildInstantBookWithFeedData($events, FeedOutputModels::CALENDAR_OUTPUT);

        $feed = $this->buildFeedWithSource(\App\Feed\CalendarApiFeedType::class);

        $result = $this->invokePrivate($instantBook, 'getBusyIntervalsFromFeed', [$feed, ['a@example.com', 'b@example.com'], $from, $to]);

        $this->assertSame(['a@example.com', 'b@example.com'], array_keys($result));
        $this->assertCount(1, $result['a@example.com']);
        $this->assertCount(1, $result['b@example.com']);
        $this->assertInstanceOf(\DateTime::class, $result['a@example.com'][0]['startTime']);
        $this->assertInstanceOf(\DateTime::class, $result['a@example.com'][0]['endTime']);
        $this->assertSame($from->getTimestamp() + 600, $result['a@example.com'][0]['startTime']->getTimestamp());
    }

    public function testGetBusyIntervalsFromFeedReturnsEmptyForResourcesWithoutEvents(): void
    {
        $from = new \DateTime('2026-01-01T10:00:00', new \DateTimeZone('UTC'));
        $to = new \DateTime('2026-01-01T11:00:00', new \DateTimeZone('UTC'));

        $instantBook = $this->buildInstantBookWithFeedData([], FeedOutputModels::CALENDAR_OUTPUT);
        $feed = $this->buildFeedWithSource(\App\Feed\CalendarApiFeedType::class);

        $result = $this->invokePrivate($instantBook, 'getBusyIntervalsFromFeed', [$feed, ['a@example.com'], $from, $to]);

        $this->assertSame(['a@example.com' => []], $result);
    }

    public function testGetBusyIntervalsFromFeedRejectsNonCalendarFeed(): void
    {
        $instantBook = $this->buildInstantBookWithFeedData([], FeedOutputModels::RSS_OUTPUT);
        $feed = $this->buildFeedWithSource(\App\Feed\RssFeedType::class);

        $this->expectException(NotAcceptableException::class);

        $this->invokePrivate($instantBook, 'getBusyIntervalsFromFeed', [$feed, ['a@example.com'], new \DateTime(), new \DateTime('+1 hour')]);
    }

    public function testGetBusyIntervalsFromFeedRejectsNullFeed(): void
    {
        $instantBook = $this->buildInstantBookWithFeedData([], FeedOutputModels::CALENDAR_OUTPUT);

        $this->expectException(NotAcceptableException::class);

        $this->invokePrivate($instantBook, 'getBusyIntervalsFromFeed', [null, ['a@example.com'], new \DateTime(), new \DateTime('+1 hour')]);
    }

    private function buildInstantBookWithFeedData(array $events, string $outputType): InstantBook
    {
        $feedType = new class($outputType) implements FeedTypeInterface {
            public function __construct(
                private readonly string $outputType,
            ) {}

            public function getData(Feed $feed): array
            {
                return [];
            }

            public function getAdminFormOptions(FeedSource $feedSource): array
            {
                return [];
            }

            public function getConfigOptions(Request $request, FeedSource $feedSource, string $name): ?array
            {
                return null;
            }

            public function getRequiredSecrets(): array
            {
                return [];
            }

            public function getRequiredConfiguration(): array
            {
                return [];
            }

            public function getSupportedFeedOutputType(): string
            {
                return $this->outputType;
            }

            public function getSchema(): array
            {
                return [];
            }
        };

        $feedService = $this->createMock(FeedService::class);
        $feedService->method('getFeedType')->willReturn($feedType);
        $feedService->method('getData')->willReturn($events);

        return new InstantBook(
            $this->container->get(InteractiveSlideService::class),
            $this->createMock(HttpClientInterface::class),
            $this->container->get(KeyVaultService::class),
            $this->createMock(CacheInterface::class),
            $feedService,
            InstantBook::SOURCE_FEED,
        );
    }

    private function buildFeedWithSource(string $feedTypeClassName): Feed
    {
        $feedSource = new FeedSource();
        $feedSource->setFeedType($feedTypeClassName);

        $feed = new Feed();
        $feed->setFeedSource($feedSource);

        return $feed;
    }

    private function invokePrivate(object $target, string $method, array $args): mixed
    {
        $ref = new \ReflectionMethod($target, $method);

        return $ref->invokeArgs($target, $args);
    }
}
