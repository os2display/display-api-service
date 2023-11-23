<?php

declare(strict_types=1);

namespace App\Command\Feed;

use App\Repository\FeedSourceRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
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
        private FeedSourceRepository $feedSourceRepository,
    ) {
        parent::__construct();
    }

    final protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->writeln('');

        $table = new Table($output);
        $table->setHeaderTitle('Installed feed sources');
        $table->setStyle('box-double');
        $table->setHeaders(['ID', 'Title', 'Tenant', 'Type']);

        $feedSources = $this->feedSourceRepository->findAll();

        foreach ($feedSources as $feedSource) {
            $feedSourceId = $feedSource->getId();
            $feedSourceTitle = $feedSource->getTitle();
            $feedSourceTenant = $feedSource->getTenant()->getTitle();
            $feedSourceType = $feedSource->getFeedType();
            $table->addRow([$feedSourceId, $feedSourceTitle, $feedSourceTenant, $feedSourceType]);
        }

        $table->render();
        $io->writeln('');

        return Command::SUCCESS;
    }
}
