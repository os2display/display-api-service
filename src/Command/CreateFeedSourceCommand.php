<?php

namespace App\Command;

use App\Entity\FeedSource;
use App\Service\FeedService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Question\Question;

#[AsCommand(
    name: 'app:feed:create-feed-source',
    description: 'Create a new feed source',
)]
class CreateFeedSourceCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager, private FeedService $feedService
    ) {
        parent::__construct();
    }

    final protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // @TODO: Add option to override existing feed source.
        // @TODO: Validate configuration and secrets again feed type.
        // @TODO: Set tenant to limit access.

        $io = new SymfonyStyle($input, $output);
        $question = new Question("Select feed type (autocompletes)");
        $question->setAutocompleterValues($this->feedService->getFeedTypes());
        $feedType = $io->askQuestion($question);

        if (!$feedType) {
            $io->error('Feed type must be set.');
            return Command::FAILURE;
        }

        $title = $io->ask("Enter title for feed source");

        if (!$title) {
            $io->error('Title must be set.');
            return Command::FAILURE;
        }

        $description = $io->ask("Describe feed source");

        $secrets = [];

        $yesNoQuestion = new Question("Add a secret?", 'No');
        $yesNoQuestion->setAutocompleterValues(['No', 'Yes']);
        while ($io->askQuestion($yesNoQuestion) !== 'No') {
            $title = $io->ask("Enter key");
            $value = $io->ask("Enter value");

            $secrets[$title] = $value;
        }

        $configuration = [];

        $yesNoQuestion = new Question("Add a configuration?", 'No');
        $yesNoQuestion->setAutocompleterValues(['No', 'Yes']);
        while ($io->askQuestion($yesNoQuestion) !== 'No') {
            $title = $io->ask("Enter key");
            $value = $io->ask("Enter value");

            $configuration[$title] = $value;
        }

        $feedSource = new FeedSource();
        $feedSource->setTitle($title);
        $feedSource->setDescription($description);
        $feedSource->setFeedType($feedType);
        $feedSource->setSecrets($secrets);
        $feedSource->setConfiguration($configuration);

        $this->entityManager->persist($feedSource);
        $this->entityManager->flush();

        $id = $feedSource->getId();
        $io->success("Feed source added with id: $id");

        return Command::SUCCESS;
    }
}
