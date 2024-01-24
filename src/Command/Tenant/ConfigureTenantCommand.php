<?php

declare(strict_types=1);

namespace App\Command\Tenant;

use App\Entity\Tenant;
use App\Repository\TenantRepository;
use App\Service\InteractiveService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:tenant:configure',
    description: 'Configure a tenant',
)]
class ConfigureTenantCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TenantRepository $tenantRepository,
        private readonly InteractiveService $interactiveService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
    }

    final protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $helper = $this->getHelper('question');

        $tenants = $this->tenantRepository->findAll();

        $question = new Question('Which tenant should be configured?');
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

        $question = new ConfirmationQuestion('Configure fallback image url (y/n)?', false);

        if ($helper->ask($input, $output, $question)) {
            $fallbackImageUrl = $io->ask('Enter fallback image url (fallbackImageUrl). Defaults to null.:');

            $tenant->setFallbackImageUrl($fallbackImageUrl);
        }

        $question = new ConfirmationQuestion('Configure interactive slides (y/n)?', false);

        if ($helper->ask($input, $output, $question)) {
            // Get available configurable interactive slide types.
            $configurables = $this->interactiveService->getConfigurables();

            foreach ($configurables as $configurable) {
                $io->info(json_encode($configurable));
            }

            // Set required config (json) in tenant.interactiveSlidesConfig.
            // Ask for configuring other interactiveSlide config.
        }

        $this->entityManager->flush();

        $tenantKey = $tenant->getTenantKey();
        $io->success("Tenant $tenantKey has been configured.");

        return Command::SUCCESS;
    }
}
