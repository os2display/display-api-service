<?php

namespace App\Tests\Utils;

use App\Entity\Feed;
use App\Entity\FeedSource;
use App\Feed\EventDatabaseApiFeedType;
use App\Feed\FeedTypeInterface;
use App\Feed\KobaFeedType;
use App\Feed\RssFeedType;
use App\Feed\SparkleIOFeedType;
use App\Service\FeedService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
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
        $this->assertEquals(EventDatabaseApiFeedType::class, $feedTypes[0]);
        $this->assertEquals(KobaFeedType::class, $feedTypes[1]);
        $this->assertEquals(RssFeedType::class, $feedTypes[2]);
        $this->assertEquals(SparkleIOFeedType::class, $feedTypes[3]);
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
}
