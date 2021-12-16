<?php

namespace App\Tests\Utils;

use App\Feed\FeedTypeInterface;
use App\Feed\RssFeedType;
use App\Repository\FeedRepository;
use App\Service\FeedService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Cache\Adapter\NullAdapter;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class FeedServiceTest extends KernelTestCase
{
    private FeedService $feedService;
    private FeedRepository $feedRepository;
    private UrlGeneratorInterface $urlGenerator;

    protected function setUp(): void
    {
        $this::bootKernel();
        $this->feedService = static::getContainer()->get(FeedService::class);
        $this->urlGenerator = static::getContainer()->get(UrlGeneratorInterface::class);
        $this->feedRepository = static::getContainer()->get(FeedRepository::class);
    }

    public function testGetFeedTypes(): void
    {
        $feedTypes = $this->feedService->getFeedTypes();
        $this->assertEquals(RssFeedType::class, $feedTypes[0]);
    }

    public function testGetFeedUrl(): void
    {
        $feed = $this->feedRepository->findOneBy([]);
        $this->assertMatchesRegularExpression('@^/v\d/\w+/([A-Za-z0-9]{26})/data$@', $this->feedService->getFeedUrl($feed));
    }

    public function testGetData(): void
    {
        $mock = $this
            ->getMockBuilder(FeedTypeInterface::class)
            ->setMockClassName('FeedTypeMock')
            ->getMock();
        $mock->method('getData')->willReturn(['test' => 'test1']);

        $nullAdapter = new NullAdapter();

        $feed = $this->feedRepository->findOneBy([]);
        $feed->getFeedSource()->setFeedType('FeedTypeMock');

        $feedService = new FeedService([$mock], $nullAdapter, $this->urlGenerator);

        $this->assertEquals(['FeedTypeMock'], $feedService->getFeedTypes());

        $data = $feedService->getData($feed);

        $this->assertEquals(['test' => 'test1'], $data);
    }
}
