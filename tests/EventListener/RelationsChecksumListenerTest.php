<?php

declare(strict_types=1);

namespace App\Tests\EventListener;

use App\Entity\ScreenLayout;
use App\Entity\ScreenLayoutRegions;
use App\Entity\Template;
use App\Entity\Tenant;
use Doctrine\ORM\EntityManager;
use Hautelook\AliceBundle\PhpUnit\BaseDatabaseTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class RelationsChecksumListenerTest extends KernelTestCase
{
    use BaseDatabaseTrait;

    private const DB_DATETIME_FORMAT = 'Y-m-d H:i:s';

    private EntityManager $em;

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
        $this->em = static::getContainer()->get('doctrine')->getManager();
    }

    public function testVersion(): void
    {
        /** @var Tenant\FeedSource $feedSource */
        $feedSource = $this->em->getRepository(Tenant\FeedSource::class)->findOneBy(['title' => 'feed_source_abc_1']);

        $version = $feedSource->getVersion();
        $this->assertEquals(1, $version);

        $feedSource->setFeedType('TEST');

        $this->em->flush();

        $this->assertEquals(2, $feedSource->getVersion());
    }

    public function testRelationsUpdatedAtPropagation(): void
    {
        /** @var Tenant\Screen $screen */
        $screen = $this->em->getRepository(Tenant\Screen::class)->findOneBy(['title' => 'screen_abc_1']);
        $beforeChecksums = $screen->getRelationsChecksum();

        /** @var Tenant\FeedSource $feedSource */
        $feedSource = $this->em->getRepository(Tenant\FeedSource::class)->findOneBy(['title' => 'feed_source_abc_1']);
        $feedSource->setFeedType('TEST');

        $this->em->flush();
        $this->em->refresh($screen);

        $afterChecksums = $screen->getRelationsChecksum();

        $this->assertNotEquals($beforeChecksums['campaigns'], $afterChecksums['campaigns']);
        $this->assertEquals($beforeChecksums['layout'], $afterChecksums['layout']);
        $this->assertEquals($beforeChecksums['regions'], $afterChecksums['regions']);

        $this->assertFalse($screen->isChanged());
    }

    public function testRemoveSlide(): void
    {
        $tenant = $this->em->getRepository(Tenant::class)->findOneBy(['tenantKey' => 'ABC']);
        /** @var Tenant\Playlist $playlist */
        $playlist = $this->em->getRepository(Tenant\Playlist::class)->findOneBy(['title' => 'playlist_abc_1', 'tenant' => $tenant]);

        $beforeChecksum = $playlist->getRelationsChecksum()['slides'];

        $playlistSlides = $playlist->getPlaylistSlides();
        $count = $playlistSlides->count();

        $keyedByDate = [];
        foreach ($playlistSlides as $playlistSlide) {
            $keyedByDate[$playlistSlide->getModifiedAt()->getTimestamp()] = $playlistSlide->getSlide();
        }
        krsort($keyedByDate);

        $oldestSlide = array_pop($keyedByDate);
        $this->em->remove($oldestSlide);
        $this->em->flush();
        $this->em->refresh($playlist);

        $this->assertEquals(--$count, $playlist->getPlaylistSlides()->count());

        $this->assertNotEquals($beforeChecksum, $playlist->getRelationsChecksum()['slides']);
        $this->assertFalse($playlist->isChanged());
    }

    public function testPersistFeed(): void
    {
        $tenant = $this->em->getRepository(Tenant::class)->findOneBy(['tenantKey' => 'ABC']);
        $feedSource = $this->em->getRepository(Tenant\FeedSource::class)->findOneBy(['tenant' => $tenant]);
        $slide = $this->em->getRepository(Tenant\Slide::class)->findOneBy(['tenant' => $tenant]);

        $feed = new Tenant\Feed();
        $feed->setTenant($tenant);
        $feed->setFeedSource($feedSource);
        $feed->setSlide($slide);
        $feed->setConfiguration(['test' => true]);

        $this->em->persist($feed);
        $this->em->flush();

        $this->em->refresh($feed);
        $this->em->refresh($feedSource);

        $relationsChecksum = $feed->getRelationsChecksum();

        $this->assertArrayHasKey('feedSource', $relationsChecksum);
        $this->assertArrayHasKey('slide', $relationsChecksum);
        $this->assertFalse($feed->isChanged());
        $this->assertFalse($feedSource->isChanged());
    }

    public function testUpdateFeedSource(): void
    {
        $tenant = $this->em->getRepository(Tenant::class)->findOneBy(['tenantKey' => 'ABC']);
        /** @var Tenant\Feed $feed */
        $feed = $this->em->getRepository(Tenant\Feed::class)->findOneBy(['tenant' => $tenant]);

        $before = $feed->getRelationsChecksum()['feedSource'];

        $feedSource = $feed->getFeedSource();
        $beforeVersion = $feedSource->getVersion();
        $feedSource->setFeedType('TEST2');

        $this->em->flush();

        $this->em->refresh($feed);
        $this->em->refresh($feedSource);

        $this->assertGreaterThan($beforeVersion, $feedSource->getVersion());
        $this->assertNotEquals($before, $feed->getRelationsChecksum()['feedSource']);
        $this->assertFalse($feed->isChanged());
        $this->assertFalse($feedSource->isChanged());
    }

    public function testPersistSlide(): void
    {
        $tenant = $this->em->getRepository(Tenant::class)->findOneBy(['tenantKey' => 'ABC']);
        $media = $this->em->getRepository(Tenant\Media::class)->findOneBy(['tenant' => $tenant]);
        $feedSource = $this->em->getRepository(Tenant\FeedSource::class)->findOneBy(['tenant' => $tenant]);
        $theme = $this->em->getRepository(Tenant\Theme::class)->findOneBy(['tenant' => $tenant]);
        $template = $this->em->getRepository(Template::class)->findOneBy(['title' => 'template_image_text']);

        $feed = new Tenant\Feed();
        $feed->setTenant($tenant);
        $feed->setFeedSource($feedSource);

        $slide = new Tenant\Slide();
        $slide->setTenant($tenant);
        $slide->addMedium($media);
        $slide->setFeed($feed);
        $slide->setTheme($theme);
        $slide->setTemplate($template);
        $slide->setTitle('testPersistSlide');

        $this->em->persist($slide);
        $this->em->flush();

        $this->em->refresh($slide);
        $this->em->refresh($feedSource);
        $this->em->refresh($feed);

        $relationsChecksum = $slide->getRelationsChecksum();

        $this->assertArrayHasKey('templateInfo', $relationsChecksum);
        $this->assertArrayHasKey('feed', $relationsChecksum);
        $this->assertArrayHasKey('media', $relationsChecksum);
        $this->assertArrayHasKey('theme', $relationsChecksum);

        $this->assertFalse($slide->isChanged());
        $this->assertFalse($feed->isChanged());
        $this->assertFalse($feedSource->isChanged());
    }

    public function testUpdateMedia(): void
    {
        $tenant = $this->em->getRepository(Tenant::class)->findOneBy(['tenantKey' => 'ABC']);
        /** @var Tenant\Slide $slide */
        $slide = $this->em->getRepository(Tenant\Slide::class)->findOneBy(['tenant' => $tenant]);

        $before = $slide->getRelationsChecksum()['media'];

        $media = $slide->getMedia();
        $this->assertGreaterThan(0, $media->count());

        /** @var Tenant\Media $medium */
        $medium = $media->first();
        $medium->setDescription('TEST');

        $this->em->flush();
        $this->em->refresh($slide);
        $this->em->refresh($medium);

        $this->assertNotEquals($before, $slide->getRelationsChecksum()['media']);
        $this->assertFalse($slide->isChanged());
        $this->assertFalse($medium->isChanged());
    }

    public function testPersistPlaylistSlide(): void
    {
        $tenant = $this->em->getRepository(Tenant::class)->findOneBy(['tenantKey' => 'ABC']);
        $playlist = $this->em->getRepository(Tenant\Playlist::class)->findOneBy(['tenant' => $tenant]);
        $slide = $this->em->getRepository(Tenant\Slide::class)->findOneBy(['tenant' => $tenant]);

        $playlistSlide = new Tenant\PlaylistSlide();
        $playlistSlide->setTenant($tenant);
        $playlistSlide->setSlide($slide);
        $playlistSlide->setPlaylist($playlist);
        $playlistSlide->setWeight(1111);

        $this->em->persist($playlistSlide);
        $this->em->flush();

        $this->em->refresh($playlistSlide);

        $relationsChecksum = $playlistSlide->getRelationsChecksum();

        $this->assertArrayHasKey('slide', $relationsChecksum);
        $this->assertFalse($playlistSlide->isChanged());
    }

    public function testUpdatePlaylistSlide(): void
    {
        /** @var Tenant\Playlist $playlist */
        $playlist = $this->em->getRepository(Tenant\Playlist::class)->findOneBy(['title' => 'playlist_abc_1']);

        $before = $playlist->getRelationsChecksum()['slides'];

        $playlistSlides = $playlist->getPlaylistSlides();
        $this->assertGreaterThan(0, $playlistSlides->count());

        /** @var Tenant\PlaylistSlide $playlistSlide */
        $playlistSlide = $playlistSlides->first();
        $modifiedAt = clone $playlistSlide->getModifiedAt();

        $playlistSlide->setWeight($playlistSlide->getWeight() + 100);

        $this->em->flush();
        $this->em->refresh($playlist);
        $this->em->refresh($playlistSlide);

        $this->assertGreaterThan($modifiedAt, $playlistSlide->getModifiedAt());
        $this->assertNotEquals($before, $playlist->getRelationsChecksum()['slides']);
        $this->assertFalse($playlist->isChanged());
        $this->assertFalse($playlistSlide->isChanged());
    }

    public function testPersistScreenCampaign(): void
    {
        $tenant = $this->em->getRepository(Tenant::class)->findOneBy(['tenantKey' => 'ABC']);
        $playlist = $this->em->getRepository(Tenant\Playlist::class)->findOneBy(['tenant' => $tenant]);
        $screen = $this->em->getRepository(Tenant\Screen::class)->findOneBy(['tenant' => $tenant]);

        $screenCampaign = new Tenant\ScreenCampaign();
        $screenCampaign->setTenant($tenant);
        $screenCampaign->setCampaign($playlist);
        $screenCampaign->setScreen($screen);
        $screenCampaign->setCreatedBy(self::class.'::testPersistScreenCampaign()');

        $this->em->persist($screenCampaign);
        $this->em->flush();

        $this->em->refresh($screenCampaign);
        $this->em->refresh($playlist);
        $this->em->refresh($screen);

        $relationsChecksum = $screenCampaign->getRelationsChecksum();

        $this->assertArrayHasKey('campaign', $relationsChecksum);
        $this->assertArrayHasKey('screen', $relationsChecksum);

        $this->assertFalse($screenCampaign->isChanged());
        $this->assertFalse($playlist->isChanged());
        $this->assertFalse($screen->isChanged());
    }

    public function testPersistScreenGroupCampaign(): void
    {
        $tenant = $this->em->getRepository(Tenant::class)->findOneBy(['tenantKey' => 'ABC']);
        $playlist = $this->em->getRepository(Tenant\Playlist::class)->findOneBy(['tenant' => $tenant]);
        $screenGroup = $this->em->getRepository(Tenant\ScreenGroup::class)->findOneBy(['tenant' => $tenant]);

        $screenGroupCampaign = new Tenant\ScreenGroupCampaign();
        $screenGroupCampaign->setTenant($tenant);
        $screenGroupCampaign->setCampaign($playlist);
        $screenGroupCampaign->setScreenGroup($screenGroup);
        $screenGroupCampaign->setCreatedBy(self::class.'::testPersistScreenGroupCampaign()');

        $this->em->persist($screenGroupCampaign);
        $this->em->flush();

        $this->em->refresh($screenGroupCampaign);
        $this->em->refresh($playlist);
        $this->em->refresh($screenGroup);

        $relationsChecksum = $screenGroupCampaign->getRelationsChecksum();

        $this->assertArrayHasKey('campaign', $relationsChecksum);
        $this->assertArrayHasKey('screenGroup', $relationsChecksum);

        $this->assertFalse($screenGroupCampaign->isChanged());
        $this->assertFalse($playlist->isChanged());
        $this->assertFalse($screenGroup->isChanged());
    }

    public function testPersistScreenGroup(): void
    {
        $tenant = $this->em->getRepository(Tenant::class)->findOneBy(['tenantKey' => 'ABC']);
        $screenGroupCampaign = $this->em->getRepository(Tenant\ScreenGroupCampaign::class)->findOneBy(['tenant' => $tenant]);
        $screen = $this->em->getRepository(Tenant\Screen::class)->findOneBy(['tenant' => $tenant]);

        $screenGroup = new Tenant\ScreenGroup();
        $screenGroup->setTenant($tenant);
        $screenGroup->addScreenGroupCampaign($screenGroupCampaign);
        $screenGroup->addScreen($screen);
        $screenGroup->setTitle('Test "testPersistScreenGroup"');
        $screenGroup->setDescription('Test "testPersistScreenGroup"');

        $this->em->persist($screenGroup);
        $this->em->flush();

        $this->em->refresh($screenGroup);
        $this->em->refresh($screenGroupCampaign);
        $this->em->refresh($screen);

        $relationsChecksum = $screenGroup->getRelationsChecksum();

        $this->assertArrayHasKey('screenGroupCampaigns', $relationsChecksum);
        $this->assertArrayHasKey('screens', $relationsChecksum);

        $this->assertFalse($screenGroup->isChanged());
        $this->assertFalse($screenGroupCampaign->isChanged());
        $this->assertFalse($screen->isChanged());
    }

    public function testPersistPlaylistScreenRegion(): void
    {
        $tenant = $this->em->getRepository(Tenant::class)->findOneBy(['tenantKey' => 'ABC']);
        $playlist = $this->em->getRepository(Tenant\Playlist::class)->findOneBy(['tenant' => $tenant]);
        $screen = $this->em->getRepository(Tenant\Screen::class)->findOneBy(['tenant' => $tenant]);
        $layout = $screen->getScreenLayout();
        $region = $layout->getRegions()->first();

        $playlistScreenRegion = new Tenant\PlaylistScreenRegion();
        $playlistScreenRegion->setTenant($tenant);
        $playlistScreenRegion->setPlaylist($playlist);
        $playlistScreenRegion->setRegion($region);
        $playlistScreenRegion->setScreen($screen);

        $this->em->persist($playlistScreenRegion);
        $this->em->flush();

        $this->em->refresh($playlistScreenRegion);

        $relationsChecksum = $playlistScreenRegion->getRelationsChecksum();
        $this->assertArrayHasKey('playlist', $relationsChecksum);
        $this->assertFalse($playlistScreenRegion->isChanged());
    }

    public function testPersistScreenLayoutRegions(): void
    {
        $tenant = $this->em->getRepository(Tenant::class)->findOneBy(['tenantKey' => 'ABC']);
        $screen = $this->em->getRepository(Tenant\Screen::class)->findOneBy(['tenant' => $tenant]);
        $layout = $screen->getScreenLayout();
        $playlistScreenRegion = $this->em->getRepository(Tenant\PlaylistScreenRegion::class)->findOneBy(['tenant' => $tenant]);

        $screenLayoutRegions = new ScreenLayoutRegions();
        $screenLayoutRegions->setTenants([$tenant]);
        $screenLayoutRegions->addPlaylistScreenRegion($playlistScreenRegion);
        $screenLayoutRegions->setScreenLayout($layout);
        $screenLayoutRegions->setTitle('testPersistScreenLayoutRegions');

        $this->em->persist($screenLayoutRegions);
        $this->em->flush();

        $this->em->refresh($screenLayoutRegions);

        $relationsChecksum = $screenLayoutRegions->getRelationsChecksum();
        $this->assertEmpty($relationsChecksum);
        $this->assertFalse($screenLayoutRegions->isChanged());
    }

    public function testPersistScreenLayout(): void
    {
        $tenant = $this->em->getRepository(Tenant::class)->findOneBy(['tenantKey' => 'ABC']);
        $screen = $this->em->getRepository(Tenant\Screen::class)->findOneBy(['tenant' => $tenant]);
        $region = $this->em->getRepository(ScreenLayoutRegions::class)->findOneBy(['title' => 'Right']);

        $screenLayout = new ScreenLayout();
        $screenLayout->setTenants([$tenant]);
        $screenLayout->addScreen($screen);
        $screenLayout->addRegion($region);
        $screenLayout->setCreatedBy(self::class.'::testPersistScreenLayout()');

        $this->em->persist($screenLayout);
        $this->em->flush();

        $this->em->refresh($screenLayout);
        $this->em->refresh($screen);

        $relationsChecksum = $screenLayout->getRelationsChecksum();
        $this->assertArrayHasKey('regions', $relationsChecksum);
        $this->assertFalse($screenLayout->isChanged());
        $this->assertFalse($screen->isChanged());
    }

    public function testPersistScreen(): void
    {
        $tenant = $this->em->getRepository(Tenant::class)->findOneBy(['tenantKey' => 'ABC']);
        $screenLayout = $this->em->getRepository(ScreenLayout::class)->findOneBy(['title' => 'Full screen']);
        $screenGroup = $this->em->getRepository(Tenant\ScreenGroup::class)->findOneBy(['tenant' => $tenant]);
        $screenCampaign = $this->em->getRepository(Tenant\ScreenCampaign::class)->findOneBy(['tenant' => $tenant]);
        $playlistScreenRegion = $this->em->getRepository(Tenant\PlaylistScreenRegion::class)->findOneBy(['tenant' => $tenant]);

        $screen = new Tenant\Screen();
        $screen->setTenant($tenant);
        $screen->setScreenLayout($screenLayout);
        $screen->addScreenGroup($screenGroup);
        $screen->addScreenCampaign($screenCampaign);
        $screen->addPlaylistScreenRegion($playlistScreenRegion);
        $screen->setCreatedBy(self::class.'::testPersistScreenLayout()');

        $this->em->persist($screen);
        $this->em->flush();

        $this->em->refresh($screen);
        $this->em->refresh($screenLayout);
        $this->em->refresh($screenGroup);
        $this->em->refresh($screenCampaign);
        $this->em->refresh($playlistScreenRegion);

        $relationsChecksum = $screen->getRelationsChecksum();
        $this->assertArrayHasKey('campaigns', $relationsChecksum);
        $this->assertArrayHasKey('layout', $relationsChecksum);
        $this->assertArrayHasKey('regions', $relationsChecksum);
        $this->assertArrayHasKey('inScreenGroups', $relationsChecksum);

        $this->assertFalse($screen->isChanged());
        $this->assertFalse($screenLayout->isChanged());
        $this->assertFalse($screenGroup->isChanged());
        $this->assertFalse($screenCampaign->isChanged());
        $this->assertFalse($playlistScreenRegion->isChanged());
    }

    public function testPlaylistSlideRelation(): void
    {
        $tenant = $this->em->getRepository(Tenant::class)->findOneBy(['tenantKey' => 'ABC']);
        /** @var Tenant\Playlist $playlist */
        $playlist = $this->em->getRepository(Tenant\Playlist::class)->findOneBy(['title' => 'playlist_abc_1', 'tenant' => $tenant]);

        $playlistSlides = $playlist->getPlaylistSlides();

        $this->assertGreaterThanOrEqual(10, $playlistSlides->count());

        $checksums = $playlist->getRelationsChecksum();
        $this->assertArrayHasKey('slides', $checksums);
        $slidesChecksum = $checksums['slides'];

        /** @var Tenant\PlaylistSlide $playlistSlide */
        $playlistSlide = $playlistSlides->first();
        $before = clone $playlistSlide->getModifiedAt();
        $playlistSlide->setWeight($playlistSlide->getWeight() + 10);

        $this->em->flush();
        $this->em->refresh($playlist);
        $this->em->refresh($playlistSlide);

        $checksums = $playlist->getRelationsChecksum();

        $this->assertGreaterThan($before, $playlistSlide->getModifiedAt());
        $this->assertNotEquals($slidesChecksum, $checksums['slides']);
    }
}
