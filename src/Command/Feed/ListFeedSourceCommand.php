<?php

namespace App\Command\Feed;

use App\Repository\FeedRepository;
use App\Repository\FeedSourceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:feed:list-feed-source',
    description: 'List feed sources',
)]
class ListFeedSourceCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private FeedSourceRepository $feedSourceRepository,
        private FeedRepository $feedRepository
    ) {
        parent::__construct();
    }

    final protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $feedSourcesMessage = "Installed feed sources (id - title (Tenant: tenant))\n\n";
        $feedSources = $this->feedSourceRepository->findAll();

        foreach ($feedSources as $feedSource) {
            $feedSourceId = $feedSource->getId();
            $feedSourceTitle = $feedSource->getTitle();
            $feedSourceTenant = $feedSource->getTenant()->getTitle();
            $feedSourcesMessage .= "$feedSourceId - $feedSourceTitle (Tenant: $feedSourceTenant)\n";
        }

        $io->info($feedSourcesMessage);

        return Command::SUCCESS;
    }
}
