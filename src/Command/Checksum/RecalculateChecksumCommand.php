<?php

declare(strict_types=1);

namespace App\Command\Checksum;

use App\Entity\Tenant\Media;
use App\Entity\Tenant\Slide;
use App\Repository\TenantRepository;
use App\Service\RelationsChecksumCalculator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Completion\CompletionInput;
use Symfony\Component\Console\Completion\CompletionSuggestions;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Stopwatch\Stopwatch;

#[AsCommand(
    name: 'app:checksum:recalculate',
    description: 'Recalculate relation checksums for slides and media entities'
)]
class RecalculateChecksumCommand extends Command
{
    private const string OPTION_TENANT = 'tenant';
    private const string OPTION_MODIFIED_AFTER = 'modified-after';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TenantRepository $tenantRepository,
        private readonly RelationsChecksumCalculator $calculator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(self::OPTION_TENANT, null, InputOption::VALUE_REQUIRED, 'Filter by tenant key')
            ->addOption(self::OPTION_MODIFIED_AFTER, null, InputOption::VALUE_REQUIRED, 'Filter by modified_at >= date (e.g. "2024-01-01" or "2024-01-01 12:00:00")')
            ->setHelp(<<<'HELP'
The <info>%command.name%</info> command recalculates relation checksums for slides and media,
then propagates the changes up the entity tree.

  <info>php %command.full_name%</info>

You can filter by tenant key and/or modification date:

  <info>php %command.full_name% --tenant=ABC</info>
  <info>php %command.full_name% --modified-after="2024-01-01"</info>
  <info>php %command.full_name% --tenant=ABC --modified-after="2024-01-01 12:00:00"</info>

Without any filters, all slides and media will be recalculated.
HELP)
        ;
    }

    public function complete(CompletionInput $input, CompletionSuggestions $suggestions): void
    {
        if ($input->mustSuggestOptionValuesFor(self::OPTION_TENANT)) {
            $tenants = $this->tenantRepository->findAll();
            foreach ($tenants as $tenant) {
                $suggestions->suggestValue($tenant->getTenantKey());
            }
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $stopwatch = new Stopwatch();
        $stopwatch->start('checksum-recalculate');

        $tenantKey = $input->getOption(self::OPTION_TENANT);
        $modifiedAfterStr = $input->getOption(self::OPTION_MODIFIED_AFTER);

        // Resolve tenant
        $tenant = null;
        if (null !== $tenantKey) {
            $tenant = $this->tenantRepository->findOneBy(['tenantKey' => $tenantKey]);
            if (null === $tenant) {
                $io->error(sprintf('Tenant with key "%s" not found.', $tenantKey));

                return Command::FAILURE;
            }
            $io->info(sprintf('Filtering by tenant: %s', $tenantKey));
        }

        // Parse date
        $modifiedAfter = null;
        if (null !== $modifiedAfterStr) {
            try {
                $modifiedAfter = new \DateTimeImmutable($modifiedAfterStr);
            } catch (\Exception) {
                $io->error(sprintf('Invalid date format: "%s". Use formats like "Y-m-d" or "Y-m-d H:i:s".', $modifiedAfterStr));

                return Command::FAILURE;
            }
            $io->info(sprintf('Filtering by modified after: %s', $modifiedAfter->format('Y-m-d H:i:s')));
        }

        // Mark matching slides and media as changed using DQL UPDATE
        $targetEntities = [
            'slide' => Slide::class,
            'media' => Media::class,
        ];
        $totalAffected = 0;

        foreach ($targetEntities as $label => $entityClass) {
            $qb = $this->entityManager->createQueryBuilder()
                ->update($entityClass, 'e')
                ->set('e.changed', ':changed')
                ->setParameter('changed', true);

            if (null !== $tenant) {
                $qb->andWhere('e.tenant = :tenant')
                    ->setParameter('tenant', $tenant);
            }

            if (null !== $modifiedAfter) {
                $qb->andWhere('e.modifiedAt >= :modifiedAfter')
                    ->setParameter('modifiedAfter', $modifiedAfter);
            }

            $affected = $qb->getQuery()->execute();
            $totalAffected += $affected;
            $io->info(sprintf('Marked %d rows in "%s" as changed.', $affected, $label));
        }

        if (0 === $totalAffected) {
            $io->warning('No rows matched the given filters. Nothing to recalculate.');

            return Command::SUCCESS;
        }

        // Propagate checksums through entity tree
        $io->info('Propagating checksums through entity tree...');
        $this->calculator->execute(withWhereClause: true);

        $event = $stopwatch->stop('checksum-recalculate');

        $io->success(sprintf(
            'Checksums recalculated. %d rows marked. Elapsed: %.2f ms, Memory: %.2f MB',
            $totalAffected,
            $event->getDuration(),
            $event->getMemory() / (1024 ** 2)
        ));

        return Command::SUCCESS;
    }
}
