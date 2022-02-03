<?php

namespace App\Command;

use App\Entity\FeedSource;
use App\Repository\FeedSourceRepository;
use App\Service\FeedService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:feed:create-feed-source',
    description: 'Create a new feed source',
)]
class CreateFeedSourceCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager, private FeedService $feedService, private FeedSourceRepository $feedSourceRepository
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('ulid', InputArgument::OPTIONAL, 'Ulid of existing feed source to override');
    }

    final protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // @TODO: Add option to override existing feed source.
        // @TODO: Validate configuration and secrets again feed type.
        // @TODO: Set tenant to limit access.
        $io = new SymfonyStyle($input, $output);

        $ulid = $input->getArgument('ulid');

        if ($ulid) {
            $io->writeln("Overriding FeedSource with ULID: $ulid");
        }

        $question = new Question('Select feed type (autocompletes)');
        $question->setAutocompleterValues($this->feedService->getFeedTypes());
        $feedType = $io->askQuestion($question);

        if (!$feedType) {
            $io->error('Feed type must be set.');

            return Command::FAILURE;
        }

        $title = $io->ask('Enter title for feed source');

        if (!$title) {
            $io->error('Title must be set.');

            return Command::FAILURE;
        }

        $description = $io->ask('Describe feed source');

        $secrets = [];

        while ($io->confirm('Add '.(0 == count($secrets) ? 'a' : 'another').' secret?', false)) {
            $key = $io->ask('Enter key');
            $value = $io->ask('Enter value');

            if ('' == $key) {
                $io->warning('key cannot be empty');
                continue;
            }

            $secrets[$key] = $value;
        }

        $configuration = [];

        while ($io->confirm('Add '.(0 == count($configuration) ? 'a' : 'another').' configuration value?', false)) {
            $key = $io->ask('Enter key');
            $value = $io->ask('Enter value');

            if ('' == $key) {
                $io->warning('key cannot be empty');
                continue;
            }

            $configuration[$key] = $value;
        }

        if ($ulid) {
            $feedSource = $this->feedSourceRepository->find($ulid);
        } else {
            $feedSource = new FeedSource();
        }

        if (!$feedSource) {
            $io->error("FeedSource with ULID: $ulid does not exist. Aborting.");

            return Command::FAILURE;
        }

        $feedSource->setTitle($title);
        $feedSource->setDescription($description);
        $feedSource->setFeedType($feedType);
        $feedSource->setSecrets($secrets);
        $feedSource->setConfiguration($configuration);

        $secretsString = implode(array_map(function ($key) use ($secrets) {
            $value = $secrets[$key];

            return " - $key: $value\n";
        }, array_keys($secrets)));
        $configurationString = implode(array_map(function ($key) use ($configuration) {
            $value = $configuration[$key];

            return " - $key: $value\n";
        }, array_keys($configuration)));
        $confirmed = $io->confirm("\n--------------\n".
            "Title: $title\n".
            "Description: $description\n".
            "Feed type: $feedType\n".
            "Secrets:\n$secretsString\n".
            "Configuration:\n$configurationString\n".
            "--------------\n".
            'Add this feed source?'
        );

        if (!$confirmed) {
            $io->warning('Abandoned.');

            return Command::FAILURE;
        }

        $this->entityManager->persist($feedSource);
        $this->entityManager->flush();

        $id = $feedSource->getId();
        $io->success("Feed source added with id: $id");

        return Command::SUCCESS;
    }
}
