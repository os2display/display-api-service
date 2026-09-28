<?php

declare(strict_types=1);

namespace App\Tests\Feed;

use App\Entity\Tenant\Feed;
use App\Entity\Tenant\FeedSource;
use App\Entity\Tenant\Slide;
use App\Feed\EventDatabaseApiV2FeedType;
use App\Feed\EventDatabaseApiV2Helper;
use App\Service\FeedService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Uid\Ulid;

/**
 * A feed whose Event Database item is gone (404) unpublishes its slide once; screens keep fetching it.
 */
class EventDatabaseApiV2FeedTypeTest extends TestCase
{
    private int $flushes = 0;

    public function testGoneItemUnpublishesThePublishedSlideAndReturnsNoData(): void
    {
        $slide = $this->publishedSlide();
        $feed = $this->feed($slide);

        $data = $this->feedType(404)->getData($feed);

        $this->assertSame([], $data);
        $this->assertNotNull($slide->getPublishedTo());
        $this->assertLessThanOrEqual(new \DateTime('now', new \DateTimeZone('UTC')), $slide->getPublishedTo());
        $this->assertSame(1, $this->flushes);
    }

    public function testGoneItemDoesNotWriteWhenTheSlideIsAlreadyUnpublished(): void
    {
        $publishedTo = new \DateTime('-1 hour', new \DateTimeZone('UTC'));
        $slide = new Slide();
        $slide->setPublishedTo($publishedTo);

        $data = $this->feedType(404)->getData($this->feed($slide));

        $this->assertSame([], $data);
        $this->assertSame($publishedTo, $slide->getPublishedTo(), 'publishedTo is not moved forward');
        $this->assertSame(0, $this->flushes, 'No database write for a slide that is already unpublished');
    }

    public function testOtherErrorsAreStillThrown(): void
    {
        $slide = $this->publishedSlide();

        $this->expectException(\Throwable::class);
        try {
            $this->feedType(500)->getData($this->feed($slide));
        } finally {
            $this->assertNull($slide->getPublishedTo());
            $this->assertSame(0, $this->flushes);
        }
    }

    private function feedType(int $status): EventDatabaseApiV2FeedType
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('flush')->willReturnCallback(function (): void {
            ++$this->flushes;
        });

        return new EventDatabaseApiV2FeedType(
            $this->createMock(FeedService::class),
            new NullLogger(),
            $entityManager,
            new EventDatabaseApiV2Helper(new MockHttpClient(new MockResponse('{}', ['http_code' => $status]))),
            new ArrayAdapter(),
            300,
        );
    }

    /**
     * A published slide as Doctrine loads it (publishedTo initialised to null).
     */
    private function publishedSlide(): Slide
    {
        $slide = new Slide();
        $slide->setPublishedTo(null);

        return $slide;
    }

    private function feed(Slide $slide): Feed
    {
        $feedSource = new FeedSource();
        $feedSource->setSecrets(['host' => 'https://eventdb.example.test', 'apikey' => 'test']);

        $feed = new Feed();
        $feed->setId(new Ulid());
        $feed->setFeedSource($feedSource);
        $feed->setConfiguration(['posterType' => 'single', 'singleSelectedOccurrence' => 1]);
        $feed->setSlide($slide);

        return $feed;
    }
}
