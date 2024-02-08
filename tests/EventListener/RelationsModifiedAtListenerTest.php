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

class RelationsModifiedAtListenerTest extends KernelTestCase
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

    public function testRelationsUpdatedAtPropagation(): void
    {
        /** @var Tenant\Screen $screen */
        $screen = $this->em->getRepository(Tenant\Screen::class)->findOneBy(['title' => 'screen_abc_1']);
        $beforeDateTime = clone $screen->getRelationsModifiedAt();
        $beforeJsom = $screen->getRelationsModified();

        /** @var Tenant\FeedSource $feedSource */
        $feedSource = $this->em->getRepository(Tenant\FeedSource::class)->findOneBy(['title' => 'feed_source_abc_1']);
        $feedSource->setFeedType('TEST');

        $this->em->flush();
        $this->em->clear();

        /** @var Tenant\FeedSource $feedSource */
        $feedSource = $this->em->getRepository(Tenant\FeedSource::class)->findOneBy(['title' => 'feed_source_abc_1']);

        /** @var Tenant\Slide $slide */
        $slide = $this->em->getRepository(Tenant\Slide::class)->findOneBy(['title' => 'slide_abc_1']);
        $this->assertEquals($slide->getRelationsModifiedAt(), $feedSource->getModifiedAt());
        $this->assertEquals($slide->getRelationsModified()['feed'], $feedSource->getModifiedAt());

        /** @var Tenant\Playlist $playlist */
        $playlist = $this->em->getRepository(Tenant\Playlist::class)->findOneBy(['title' => 'playlist_abc_1']);
        $this->assertEquals($playlist->getRelationsModifiedAt(), $feedSource->getModifiedAt());
        $this->assertEquals($playlist->getRelationsModified()['slides'], $feedSource->getModifiedAt());

        /** @var Tenant\Screen $screen */
        $screen = $this->em->getRepository(Tenant\Screen::class)->findOneBy(['title' => 'screen_abc_1']);
        $this->assertEquals($screen->getRelationsModifiedAt(), $feedSource->getModifiedAt());
        $this->assertEquals($screen->getRelationsModified()['campaigns'], $feedSource->getModifiedAt());

        $afterDateTime = $screen->getRelationsModifiedAt();
        $afterJsom = $screen->getRelationsModified();
        $this->assertGreaterThan($beforeDateTime, $afterDateTime);
        $this->assertGreaterThan($beforeJsom['campaigns'], $afterJsom['campaigns']);

        $max = max($screen->getRelationsModified());
        $this->assertEquals($max, $screen->getRelationsModifiedAt());
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

        $relationsModified = $feed->getRelationsModified();

        $this->assertArrayHasKey('feedSource', $relationsModified);
        $this->assertArrayHasKey('slide', $relationsModified);
        $this->assertRelationsAtEqualsMax($feed->getRelationsModifiedAt(), $relationsModified);
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

        $relationsModified = $slide->getRelationsModified();

        $this->assertArrayHasKey('templateInfo', $relationsModified);
        $this->assertArrayHasKey('feed', $relationsModified);
        $this->assertArrayHasKey('media', $relationsModified);
        $this->assertArrayHasKey('theme', $relationsModified);

        $this->assertDateTimeEqualsByJsonFormat($template->getModifiedAt(), $relationsModified['templateInfo']);
        $this->assertDateTimeEqualsByJsonFormat(max($feed->getRelationsModifiedAt(), $feed->getModifiedAt()), $relationsModified['feed']);
        $this->assertDateTimeEqualsByJsonFormat($theme->getModifiedAt(), $relationsModified['theme']);
        $this->assertDateTimeEqualsByJsonFormat($media->getModifiedAt(), $relationsModified['media']);

        $this->assertRelationsAtEqualsMax($slide->getRelationsModifiedAt(), $relationsModified);
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

        $relationsModified = $playlistSlide->getRelationsModified();

        $this->assertArrayHasKey('slide', $relationsModified);

        $this->assertRelationsAtEqualsMax($playlistSlide->getRelationsModifiedAt(), $relationsModified);
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

        $relationsModified = $screenCampaign->getRelationsModified();

        $this->assertArrayHasKey('campaign', $relationsModified);
        $this->assertArrayHasKey('screen', $relationsModified);

        $this->assertDateTimeEqualsByJsonFormat(max($playlist->getRelationsModifiedAt(), $playlist->getModifiedAt()), $relationsModified['campaign']);
        $this->assertDateTimeEqualsByJsonFormat($screen->getModifiedAt(), $relationsModified['screen']);

        $this->assertRelationsAtEqualsMax($screenCampaign->getRelationsModifiedAt(), $relationsModified);
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

        $relationsModified = $screenGroupCampaign->getRelationsModified();

        $this->assertArrayHasKey('campaign', $relationsModified);
        $this->assertArrayHasKey('screenGroup', $relationsModified);

        $max = max($playlist->getRelationsModifiedAt(), $playlist->getModifiedAt());

        $this->assertDateTimeEqualsByJsonFormat(max($playlist->getRelationsModifiedAt(), $playlist->getModifiedAt()), $relationsModified['campaign']);
        $this->assertEquals(max($screenGroup->getRelationsModifiedAt(), $screenGroup->getModifiedAt()), $relationsModified['screenGroup']);

        $this->assertRelationsAtEqualsMax($screenGroupCampaign->getRelationsModifiedAt(), $relationsModified);
    }

    public function testPersistScreenGroupDebug(): void
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

        $relationsModified = $screenGroup->getRelationsModified();

        $this->assertArrayHasKey('screenGroupCampaigns', $relationsModified);
        $this->assertArrayHasKey('screens', $relationsModified);

        $this->assertDateTimeEqualsByJsonFormat(max($screenGroupCampaign->getRelationsModifiedAt(), $screenGroupCampaign->getModifiedAt()), $relationsModified['screenGroupCampaigns']);
        $this->assertDateTimeEqualsByJsonFormat($screen->getModifiedAt(), $relationsModified['screens']);

        $this->assertRelationsAtEqualsMax($screenGroup->getRelationsModifiedAt(), $relationsModified);
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

        $relationsModified = $playlistScreenRegion->getRelationsModified();
        $this->assertArrayHasKey('playlist', $relationsModified);

        $this->assertRelationsAtEqualsMax($playlistScreenRegion->getRelationsModifiedAt(), $relationsModified);
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

        $relationsModified = $screenLayoutRegions->getRelationsModified();
        $this->assertEmpty($relationsModified);

        $expected = max($playlistScreenRegion->getRelationsModifiedAt(), $playlistScreenRegion->getModifiedAt(), $layout->getRelationsModifiedAt(), $layout->getModifiedAt());
        $this->assertDateTimeEqualsByJsonFormat($expected, $screenLayoutRegions->getRelationsModifiedAt());
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

        $relationsModified = $screenLayout->getRelationsModified();
        $this->assertArrayHasKey('regions', $relationsModified);
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

        $relationsModified = $screen->getRelationsModified();
        $this->assertArrayHasKey('campaigns', $relationsModified);
    }

    public function testPlaylistSlideRelation(): void
    {
        $tenant = $this->em->getRepository(Tenant::class)->findOneBy(['tenantKey' => 'ABC']);
        $playlist = $this->em->getRepository(Tenant\Playlist::class)->findOneBy(['title' => 'playlist_abc_1', 'tenant' => $tenant]);

        /** @var Tenant\Playlist $playlist */
        $playlistSlides = $playlist->getPlaylistSlides();

        $newest = $playlistSlides->first()->getSlide()->getModifiedAt();
        $oldest = $playlistSlides->first()->getSlide()->getModifiedAt();

        foreach ($playlistSlides as $playlistSlide) {
            // Assert PlaylistSlide values correct relative to Slide
            $slide = $playlistSlide->getSlide();
            $expected = max($slide->getRelationsModifiedAt(), $slide->getModifiedAt());
            $this->assertEquals($expected->getTimestamp(), $playlistSlide->getRelationsModifiedAt()->getTimestamp(), 'PlaylistSlide');

            $newest = max($newest, $slide->getModifiedAt(), $slide->getRelationsModifiedAt());
            $oldest = min($oldest, $slide->getModifiedAt(), $slide->getRelationsModifiedAt());
        }
        $this->assertGreaterThanOrEqual(10, $playlistSlides->count());
        $this->assertEquals($playlist->getRelationsModifiedAt()->getTimestamp(), $newest->getTimestamp());
    }

    private function assertRelationsAtEqualsMax(?\DateTimeImmutable $relationsModifiedAt, array $relationsModified): void
    {
        $this->assertEquals($relationsModifiedAt, max($relationsModified));
    }

    private function assertDateTimeEqualsByJsonFormat(?\DateTimeImmutable $expected, ?\DateTimeImmutable $actual): void
    {
        $expected = $expected?->format(self::DB_DATETIME_FORMAT);
        $actual = $actual?->format(self::DB_DATETIME_FORMAT);

        $this->assertEquals($expected, $actual);
    }
}
