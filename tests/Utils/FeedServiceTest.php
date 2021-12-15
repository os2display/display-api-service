<?php

namespace App\Tests\Utils;

use ApiPlatform\Core\Exception\InvalidArgumentException;
use App\Feed\RssFeedType;
use App\Service\FeedService;
use App\Utils\IriHelperUtils;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class FeedServiceTest extends KernelTestCase
{
    private FeedService $feedService;

    protected function setUp(): void
    {
        $this::bootKernel();
        $this->feedService = static::getContainer()->get(FeedService::class);
    }

    public function testGetFeedTypes(): void
    {
        $feedTypes = $this->feedService->getFeedTypes();
        $this->assertEquals(RssFeedType::class, $feedTypes[0]);
    }
}
