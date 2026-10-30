<?php

declare(strict_types=1);

namespace App\Command\Feed;

use App\Entity\Tenant\FeedSource;
use App\Service\DeprecatedFeedSourceFinder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:feed:remove-deprecated-feed-sources',
    description: 'Report and remove feed sources that reference a feed type removed in 3.0.0',
)]
class RemoveDeprecatedFeedSourcesCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly DeprecatedFeedSourceFinder $finder,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'force',
            null,
            InputOption::VALUE_NONE,
            'Actually remove the deprecated feed sources (and their feeds and slides). Without this flag the command only reports.',
        );
    }

    final protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $force = (bool) $input->getOption('force');

        $feedSources = $this->finder->findDeprecated();

        if (0 === count($feedSources)) {
            $io->success('No feed sources reference a deprecated feed type. Nothing to do.');

            return Command::SUCCESS;
        }

        $this->report($io, $feedSources);

        if (!$force) {
            $io->note('This was a dry run. Re-run with --force to remove the feed sources, their feeds and the associated slides.');

            return Command::SUCCESS;
        }

        if ($input->isInteractive() && !$io->confirm(sprintf('Remove %d feed source(s) together with their feeds and slides? This cannot be undone.', count($feedSources)), false)) {
            $io->info('Aborted. No changes made.');

            return Command::SUCCESS;
        }

        [$removedFeedSources, $removedFeeds, $removedSlides] = $this->remove($feedSources);

        $io->success(sprintf(
            'Removed %d feed source(s), %d feed(s) and %d slide(s).',
            $removedFeedSources,
            $removedFeeds,
            $removedSlides,
        ));

        return Command::SUCCESS;
    }

    /**
     * @param FeedSource[] $feedSources
     */
    private function report(SymfonyStyle $io, array $feedSources): void
    {
        $io->section(sprintf('Found %d feed source(s) referencing a deprecated feed type', count($feedSources)));

        $rows = [];
        foreach ($feedSources as $feedSource) {
            $feeds = $feedSource->getFeeds();
            $slideCount = 0;
            foreach ($feeds as $feed) {
                if (null !== $feed->getSlide()) {
                    ++$slideCount;
                }
            }

            $rows[] = [
                (string) $feedSource->getId(),
                $feedSource->getTitle(),
                $feedSource->getTenant()->getTenantKey(),
                $feedSource->getFeedType() ?? '',
                count($feeds),
                $slideCount,
            ];
        }

        $io->table(['Feed source', 'Title', 'Tenant', 'Feed type', 'Feeds', 'Slides'], $rows);
    }

    /**
     * Remove the feed sources together with their feeds and the slides bound to
     * those feeds.
     *
     * Removing a Slide cascades to its PlaylistSlide rows and its Feed (Slide owns
     * the relation with cascade: ['remove'] + orphanRemoval — see
     * App\Entity\Tenant\Slide). Removing the FeedSource then orphan-removes any
     * remaining feeds that had no slide.
     *
     * @param FeedSource[] $feedSources
     *
     * @return array{0: int, 1: int, 2: int} removed feed sources, feeds and slides
     */
    private function remove(array $feedSources): array
    {
        $removedFeeds = 0;
        $removedSlides = 0;

        foreach ($feedSources as $feedSource) {
            foreach ($feedSource->getFeeds() as $feed) {
                ++$removedFeeds;

                $slide = $feed->getSlide();
                if (null !== $slide) {
                    ++$removedSlides;
                    $this->entityManager->remove($slide);
                }
            }

            $this->entityManager->remove($feedSource);
        }

        $this->entityManager->flush();

        return [count($feedSources), $removedFeeds, $removedSlides];
    }
}
