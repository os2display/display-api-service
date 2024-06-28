<?php

declare(strict_types=1);

namespace App\Tests\Feed;

use App\Feed\NotifiedFeedType;
use App\Repository\FeedSourceRepository;
use App\Repository\SlideRepository;
use App\Service\FeedService;
use App\Tests\AbstractBaseApiTestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class NotifiedFeedTypeTest extends AbstractBaseApiTestCase
{
    public function testGetFeed(): void
    {
        $container = static::getContainer();
        $feedSourceRepository = $container->get(FeedSourceRepository::class);

        $feedSource = $feedSourceRepository->findOneBy(['title' => 'feed_source_abc_notified']);

        $feedService = $container->get(FeedService::class);
        $logger = $container->get(LoggerInterface::class);

        $httpClientMock = $this->createMock(HttpClientInterface::class);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn(NotifiedFeedTypeData::getConfigData());

        $httpClientMock->method('request')->willReturn($response);

        $notifiedFeedType = new NotifiedFeedType($feedService, $httpClientMock, $logger);

        $feeds = $notifiedFeedType->getConfigOptions(new Request(), $feedSource, 'feeds');

        $this->assertEquals(12345, $feeds[0]['value']);
        $this->assertEquals('Test1', $feeds[0]['title']);
        $this->assertEquals(12346, $feeds[1]['value']);
        $this->assertEquals('Test3', $feeds[1]['title']);

        $httpClientMock = $this->createMock(HttpClientInterface::class);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn(
            NotifiedFeedTypeData::getData()
        );
        $response->method('getStatusCode')->willReturn(200);

        $httpClientMock->method('request')->willReturn($response);

        $notifiedFeedType = new NotifiedFeedType($feedService, $httpClientMock, $logger);

        $slideRepository = $container->get(SlideRepository::class);

        $slide = $slideRepository->findOneBy(['title' => 'slide_abc_notified']);

        $feed = $slide->getFeed();

        $data = $notifiedFeedType->getData($feed);

        $this->assertCount(1, $data);
    }
}
