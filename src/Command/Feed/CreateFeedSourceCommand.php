<?php

declare(strict_types=1);

namespace App\Command\Feed;

use App\Entity\Tenant;
use App\Entity\Tenant\FeedSource;
use App\Repository\FeedSourceRepository;
use App\Repository\TenantRepository;
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
        private readonly EntityManagerInterface $entityManager,
        private readonly FeedService $feedService,
        private readonly FeedSourceRepository $feedSourceRepository,
        private readonly TenantRepository $tenantRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('ulid', InputArgument::OPTIONAL, 'Ulid of existing feed source to override');
    }

    final protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Check if ULID option is set.
        $ulid = $input->getArgument('ulid');
        if (!is_null($ulid)) {
            $io->writeln("Overriding FeedSource with ULID: $ulid");
        }

        // Ask user for feed type base on available types.
        $feedTypes = $this->feedService->getFeedTypes();
        $question = new Question('Select feed type (autocompletes)');
        $question->setAutocompleterValues($feedTypes);
        $feedTypeClassname = $io->askQuestion($question);
        if (!$feedTypeClassname) {
            $io->error('Feed type must be set.');

            return Command::FAILURE;
        }

        $io->info("Selected feed type: $feedTypeClassname");

        $feedType = $this->feedService->getFeedType($feedTypeClassname);
        $tenants = $this->tenantRepository->findAll();

        // Ask user for which tenant to use.
        $question = new Question('Which tenant should the feed source be added to?');
        $question->setAutocompleterValues(array_reduce($tenants, function (array $carry, Tenant $tenant) {
            $carry[$tenant->getTenantKey()] = $tenant->getTenantKey();

            return $carry;
        }, []));
        $tenantSelected = $io->askQuestion($question);
        if (empty($tenantSelected)) {
            $io->error('No tenant selected. Aborting.');

            return Command::INVALID;
        }
        $tenant = $this->tenantRepository->findOneBy(['tenantKey' => $tenantSelected]);
        if (null == $tenant) {
            $io->error('Tenant not found.');

            return Command::INVALID;
        }
        $io->info("Feed source will be added to $tenantSelected tenant.");

        $title = $io->ask('Enter title for feed source');
        if (!$title) {
            $io->error('Title must be set.');

            return Command::FAILURE;
        }

        $description = $io->ask('Describe feed source');

        $secrets = [];
        $io->info('Set required secrets.');
        $requiredSecrets = $feedType->getRequiredSecrets();
        foreach ($requiredSecrets as $requiredSecret) {
            $value = null;
            do {
                $value = $io->ask("Enter \"$requiredSecret\": ");

                if ('' == $value) {
                    $io->warning('Value cannot be empty');
                }
            } while ('' == $value);

            $secrets[$requiredSecret] = $value;
        }

        if (array_keys($secrets) != $requiredSecrets) {
            $io->error('Not all secrets set');

            return Command::INVALID;
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
        $feedSource->setFeedType($feedTypeClassname);
        $feedSource->setSecrets($secrets);
        $feedSource->setSupportedFeedOutputType($feedType->getSupportedFeedOutputType());
        $feedSource->setTenant($tenant);

        $secretsString = implode('', array_map(function ($key) use ($secrets) {
            $value = $secrets[$key];

            return " - $key: $value\n";
        }, array_keys($secrets)));
        $confirmed = $io->confirm("\n--------------\n".
            "Title: $title\n".
            "Description: $description\n".
            "Feed type: $feedTypeClassname\n".
            "Secrets:\n$secretsString\n".
            "--------------\n".
            'Add this feed source?'
        );

        if (!$confirmed) {
            $io->warning('Abandoned.');

            return Command::FAILURE;
        }

        // Persist new feed source to the database.
        $this->entityManager->persist($feedSource);
        $this->entityManager->flush();

        $id = $feedSource->getId();
        $io->success("Feed source added with id: $id");

        return Command::SUCCESS;
    }
}
