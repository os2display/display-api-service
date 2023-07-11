<?php

namespace App\Command\Feed;

use App\Service\FeedService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:feed:get-feed-types',
    description: 'Get a list of available feed types',
)]
class GetFeedTypesCommand extends Command
{
    public function __construct(
        private FeedService $feedService
    ) {
        parent::__construct();
    }

    final protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $str = 'Available feed types:'.PHP_EOL.PHP_EOL;
        foreach ($this->feedService->getFeedTypes() as $feedType) {
            $str .= $feedType.PHP_EOL;
        }
        $io->info($str);

        return Command::SUCCESS;
    }
}
