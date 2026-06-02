<?php

declare(strict_types=1);

namespace App\Tests\Command\Feed;

use App\Entity\Template;
use App\Entity\Tenant;
use App\Entity\Tenant\Feed;
use App\Entity\Tenant\FeedSource;
use App\Entity\Tenant\Playlist;
use App\Entity\Tenant\PlaylistSlide;
use App\Entity\Tenant\Slide;
use App\Tests\AbstractBaseApiTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Uid\Ulid;

class RemoveDeprecatedFeedSourcesCommandTest extends AbstractBaseApiTestCase
{
    private const string DEPRECATED_FEED_TYPE = 'App\\Feed\\KobaFeedType';

    public function testDryRunReportsButKeepsFeedSource(): void
    {
        [$feedSourceId] = $this->createDeprecatedFeedSourceGraph('dry-run feed source');

        $tester = $this->runCommand([]);

        $output = $tester->getDisplay();
        $this->assertStringContainsString('dry-run feed source', $output);
        $this->assertStringContainsString('dry run', $output);

        // Nothing should have been removed.
        $this->assertNotNull($this->entityManager()->getRepository(FeedSource::class)->find($feedSourceId));
    }

    public function testForceRemovesFeedSourceFeedAndSlide(): void
    {
        [$feedSourceId, $feedId, $slideId, $playlistSlideId] = $this->createDeprecatedFeedSourceGraph('force feed source');

        $tester = $this->runCommand(['--force' => true]);
        $this->assertSame(0, $tester->getStatusCode());

        $em = $this->entityManager();
        $em->clear();

        // The feed source, its feed, the bound slide and the slide's playlist
        // relation must all be gone (Slide cascades to Feed + PlaylistSlide).
        $this->assertNull($em->getRepository(FeedSource::class)->find($feedSourceId), 'feed source removed');
        $this->assertNull($em->getRepository(Feed::class)->find($feedId), 'feed removed');
        $this->assertNull($em->getRepository(Slide::class)->find($slideId), 'slide removed');
        $this->assertNull($em->getRepository(PlaylistSlide::class)->find($playlistSlideId), 'playlist slide removed');
    }

    /**
     * Build FeedSource -> Feed -> Slide -> PlaylistSlide referencing a removed feed type.
     *
     * @return array{0: Ulid, 1: Ulid, 2: Ulid, 3: Ulid}
     */
    private function createDeprecatedFeedSourceGraph(string $title): array
    {
        $em = $this->entityManager();

        $tenant = $em->getRepository(Tenant::class)->findOneBy(['tenantKey' => 'ABC']);
        $this->assertInstanceOf(Tenant::class, $tenant);
        $template = $em->getRepository(Template::class)->findOneBy([]);
        $this->assertInstanceOf(Template::class, $template);

        $feedSource = new FeedSource();
        $feedSource->setTitle($title);
        $feedSource->setDescription('Deprecated feed source for cleanup test');
        $feedSource->setFeedType(self::DEPRECATED_FEED_TYPE);
        $feedSource->setSupportedFeedOutputType('calendar');
        $feedSource->setTenant($tenant);
        $em->persist($feedSource);

        $slide = new Slide();
        $slide->setTitle($title.' slide');
        $slide->setTemplate($template);
        $slide->setTenant($tenant);
        $em->persist($slide);

        $feed = new Feed();
        $feed->setFeedSource($feedSource);
        $feed->setTenant($tenant);
        $em->persist($feed);

        // Slide owns the relation to Feed.
        $slide->setFeed($feed);

        $playlist = new Playlist();
        $playlist->setTitle($title.' playlist');
        $playlist->setTenant($tenant);
        $em->persist($playlist);

        $playlistSlide = new PlaylistSlide();
        $playlistSlide->setPlaylist($playlist);
        $playlistSlide->setSlide($slide);
        $playlistSlide->setTenant($tenant);
        $em->persist($playlistSlide);

        $em->flush();

        $ids = [$feedSource->getId(), $feed->getId(), $slide->getId(), $playlistSlide->getId()];
        $em->clear();

        return $ids;
    }

    private function runCommand(array $input): CommandTester
    {
        $application = new Application(self::$kernel);
        $command = $application->find('app:feed:remove-deprecated-feed-sources');
        $tester = new CommandTester($command);
        // Run non-interactively so --force skips the confirmation prompt.
        $tester->execute($input, ['interactive' => false]);

        return $tester;
    }

    private function entityManager(): EntityManagerInterface
    {
        return static::getContainer()->get(EntityManagerInterface::class);
    }
}
