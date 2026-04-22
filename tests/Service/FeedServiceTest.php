<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Tenant\Feed;
use App\Entity\Tenant\FeedSource;
use App\Feed\CalendarApiFeedType;
use App\Feed\EventDatabaseApiFeedType;
use App\Feed\FeedTypeInterface;
use App\Feed\KobaFeedType;
use App\Feed\NotifiedFeedType;
use App\Feed\RssFeedType;
use App\Feed\SparkleIOFeedType;
use App\Service\FeedService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\NullAdapter;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class FeedServiceTest extends KernelTestCase
{
    private FeedService $feedService;
    private EntityManagerInterface $entityManager;
    private UrlGeneratorInterface $urlGenerator;

    protected function setUp(): void
    {
        $this::bootKernel();
        $this->feedService = static::getContainer()->get(FeedService::class);
        $this->urlGenerator = static::getContainer()->get(UrlGeneratorInterface::class);
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testGetFeedTypes(): void
    {
        $feedTypes = $this->feedService->getFeedTypes();
        $this->assertTrue(in_array(CalendarApiFeedType::class, $feedTypes));
        $this->assertTrue(in_array(EventDatabaseApiFeedType::class, $feedTypes));
        $this->assertTrue(in_array(KobaFeedType::class, $feedTypes));
        $this->assertTrue(in_array(NotifiedFeedType::class, $feedTypes));
        $this->assertTrue(in_array(RssFeedType::class, $feedTypes));
        $this->assertTrue(in_array(SparkleIOFeedType::class, $feedTypes));
    }

    public function testGetFeedUrl(): void
    {
        $feedSource = new FeedSource();
        $feedSource->setTitle('123');
        $feedSource->setDescription('123');
        $feedSource->setFeedType(RssFeedType::class);
        $this->entityManager->persist($feedSource);

        $feed = new Feed();
        $feed->setFeedSource($feedSource);
        $this->entityManager->persist($feed);

        $this->assertMatchesRegularExpression('@^/v\d/\w+/([A-Za-z0-9]{26})/data$@', $this->feedService->getRemoteFeedUrl($feed));
    }

    public function testGetData(): void
    {
        $mock = $this
            ->getMockBuilder(FeedTypeInterface::class)
            ->setMockClassName('FeedTypeMock')
            ->getMock();
        $mock->method('getData')->willReturn(['test' => 'test1']);

        $nullAdapter = new NullAdapter();

        $feedSource = new FeedSource();
        $feedSource->setTitle('123');
        $feedSource->setDescription('123');
        $feedSource->setFeedType('FeedTypeMock');
        $this->entityManager->persist($feedSource);

        $feed = new Feed();
        $feed->setFeedSource($feedSource);
        $this->entityManager->persist($feed);

        $feedService = new FeedService([$mock], $nullAdapter, $this->urlGenerator);

        $this->assertEquals(['FeedTypeMock'], $feedService->getFeedTypes());

        $data = $feedService->getData($feed);

        $this->assertEquals(['test' => 'test1'], $data);
    }

    public function testGetDataErrorIsNotCachedWithNormalTtl(): void
    {
        $mock = $this
            ->getMockBuilder(FeedTypeInterface::class)
            ->setMockClassName('FeedTypeErrorMock')
            ->getMock();
        $mock->method('getData')->willThrowException(new \RuntimeException('API unavailable'));

        $cache = new ArrayAdapter();

        $feedSource = new FeedSource();
        $feedSource->setTitle('123');
        $feedSource->setDescription('123');
        $feedSource->setFeedType('FeedTypeErrorMock');
        $this->entityManager->persist($feedSource);

        $feed = new Feed();
        $feed->setFeedSource($feedSource);
        $feed->setConfiguration(['cache_expire' => 3600]);
        $this->entityManager->persist($feed);

        $feedService = new FeedService([$mock], $cache, $this->urlGenerator);

        // First call should return empty array.
        $data = $feedService->getData($feed);
        $this->assertEquals([], $data);

        // The empty result should be cached with a short TTL, not the normal 3600s.
        // Verify by replacing the mock with one that returns data. If the error result
        // were cached with the normal TTL, this would still return [].
        $successMock = $this
            ->getMockBuilder(FeedTypeInterface::class)
            ->setMockClassName('FeedTypeErrorMock')
            ->getMock();
        $successMock->method('getData')->willReturn(['test' => 'success']);

        $feedService = new FeedService([$successMock], $cache, $this->urlGenerator);

        // Within the short TTL window, the cached empty result is returned.
        $data = $feedService->getData($feed);
        $this->assertEquals([], $data);
    }

    public function testGetDataErrorDoesNotCacheWithNormalTtl(): void
    {
        $callCount = 0;

        $mock = $this
            ->getMockBuilder(FeedTypeInterface::class)
            ->setMockClassName('FeedTypeCountMock')
            ->getMock();
        $mock->method('getData')->willReturnCallback(function () use (&$callCount) {
            ++$callCount;
            throw new \RuntimeException('API unavailable');
        });

        // Use storeSerialized=false so TTL 0 entries expire immediately.
        $cache = new ArrayAdapter(defaultLifetime: 0, storeSerialized: false);

        $feedSource = new FeedSource();
        $feedSource->setTitle('123');
        $feedSource->setDescription('123');
        $feedSource->setFeedType('FeedTypeCountMock');
        $this->entityManager->persist($feedSource);

        $feed = new Feed();
        $feed->setFeedSource($feedSource);
        $feed->setConfiguration(['cache_expire' => 3600]);
        $this->entityManager->persist($feed);

        $feedService = new FeedService([$mock], $cache, $this->urlGenerator);

        // First call triggers getData.
        $feedService->getData($feed);
        $this->assertEquals(1, $callCount);

        // Second call within TTL should use cached empty result, not call getData again.
        $feedService->getData($feed);
        $this->assertEquals(1, $callCount);
    }
}
