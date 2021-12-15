<?php

namespace App\Tests\Utils;

use App\Feed\RssFeedType;
use App\Repository\FeedRepository;
use App\Service\FeedService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class FeedServiceTest extends KernelTestCase
{
    private FeedService $feedService;
    private FeedRepository $feedRepository;

    protected function setUp(): void
    {
        $this::bootKernel();
        $this->feedService = static::getContainer()->get(FeedService::class);
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
        // @TODO: Test this.
        // $feed = $this->feedRepository->findOneBy([]);
        // $data = $this->feedService->getFeedUrl($feed);
    }
}
