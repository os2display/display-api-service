<?php

declare(strict_types=1);

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command\Tenant;

use App\Entity\Tenant;
use App\Exceptions\EntityException;
use App\Repository\TenantRepository;
use App\Utils\CommandInputValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Stopwatch\Stopwatch;

use function Symfony\Component\String\u;

/**
 * A console command that creates tenants and stores them in the database.
 *
 * To use this command, open a terminal window, enter into your project
 * directory and execute the following:
 *
 *     $ php bin/console app:tenant:add
 *
 * To output detailed information, increase the command verbosity:
 *
 *     $ php bin/console app:tenant:add -vv
 *
 * See https://symfony.com/doc/current/console.html
 *
 * We use the default services.yaml configuration, so command classes are registered as services.
 * See https://symfony.com/doc/current/console/commands_as_services.html
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
#[AsCommand(
    name: 'app:tenant:add',
    description: 'Creates tenants and stores them in the database'
)]
class AddTenantCommand extends Command
{
    private const string TENANT_KEY_ARGUMENT = 'tenantKey';
    private const string TITLE_ARGUMENT = 'title';
    private const string DESCRIPTION_ARGUMENT = 'description';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CommandInputValidator $validator,
        private readonly TenantRepository $tenants,
    ) {
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setHelp($this->getCommandHelp())
            // commands can optionally define arguments and/or options (mandatory and optional)
            // see https://symfony.com/doc/current/components/console/console_arguments.html
            ->addArgument(self::TENANT_KEY_ARGUMENT, InputArgument::OPTIONAL, 'The unique key of the new tenant')
            ->addArgument(self::TITLE_ARGUMENT, InputArgument::OPTIONAL, 'The title of the new tenant')
            ->addArgument(self::DESCRIPTION_ARGUMENT, InputArgument::OPTIONAL, 'The description of the new tenant')
        ;
    }

    /**
     * This method is executed after initialize() and before execute().
     *
     * Its purpose is to check if some of the options/arguments are missing and interactively ask the user for those
     * values.
     *
     * This method is completely optional. If you are developing an internal console command, you probably should not
     * implement this method because it requires quite a lot of work. However, if the command is meant to be used by
     * external users, this method is a nice way to fall back and prevent errors.
     */
    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        if (null !== $input->getArgument(self::TENANT_KEY_ARGUMENT) && null !== $input->getArgument(self::TITLE_ARGUMENT) && null !== $input->getArgument(self::DESCRIPTION_ARGUMENT)) {
            return;
        }

        $io = new SymfonyStyle($input, $output);

        $io->title('Add Tenant Command Interactive Wizard');
        $io->text([
            'If you prefer to not use this interactive wizard, provide the',
            'arguments required by this command as follows:',
            '',
            ' $ php bin/console app:tenant:add tenantKey',
            '',
            'Now we\'ll ask you for the value of all the missing command arguments.',
        ]);

        // Ask for the tenant key if it's not defined
        $tenantKey = $input->getArgument(self::TENANT_KEY_ARGUMENT);
        if (null !== $tenantKey) {
            $io->text(' > <info>Tenant Key</info>: '.$tenantKey);
        } else {
            $tenantKey = $io->ask('Tenant Key', null, $this->validator->validateTenantKey(...));
            $input->setArgument(self::TENANT_KEY_ARGUMENT, $tenantKey);
        }

        // Ask for the password if it's not defined
        $title = $input->getArgument(self::TITLE_ARGUMENT);
        if (null !== $title) {
            $io->text(' > <info>Title</info>: '.u('*')->repeat(u($title)->length()));
        } else {
            $title = $io->ask('Title');
            $input->setArgument(self::TITLE_ARGUMENT, $title);
        }

        // Ask for the full name if it's not defined
        $description = $input->getArgument(self::DESCRIPTION_ARGUMENT);
        if (null !== $description) {
            $io->text(' > <info>Description</info>: '.$description);
        } else {
            $description = $io->ask('Description');
            $input->setArgument(self::DESCRIPTION_ARGUMENT, $description);
        }
    }

    /**
     * This method is executed after interact() and initialize().
     *
     * It usually contains the logic to execute to complete this command task.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $stopwatch = new Stopwatch();
        $stopwatch->start('add-tenant-command');

        $tenantKey = $input->getArgument(self::TENANT_KEY_ARGUMENT);
        $title = $input->getArgument(self::TITLE_ARGUMENT) ?? '';
        $description = $input->getArgument(self::DESCRIPTION_ARGUMENT) ?? '';

        // Make sure to validate the user data is correct.
        if (null == $tenantKey) {
            throw new RuntimeException('Tenant key should not be null');
        }
        $this->validateTenantData($tenantKey);

        // Create the user and hash its password.
        $tenant = new Tenant();
        $tenant->setTenantKey($tenantKey);
        $tenant->setTitle($title);
        $tenant->setDescription($description);
        $tenant->setCreatedBy('CLI');

        $this->entityManager->persist($tenant);
        $this->entityManager->flush();

        $io = new SymfonyStyle($input, $output);

        $io->success(sprintf('Tenant was successfully created: %s', $tenant->getTenantKey()));

        $event = $stopwatch->stop('add-tenant-command');
        if ($output->isVerbose()) {
            $tenantId = $tenant->getId();

            if (null === $tenantId) {
                throw new EntityException('Tenant id null');
            }

            $io->comment(sprintf('New tenant database id: %s / Elapsed time: %.2f ms / Consumed memory: %.2f MB', $tenantId->jsonSerialize(), $event->getDuration(), $event->getMemory() / (1024 ** 2)));
        }

        return Command::SUCCESS;
    }

    private function validateTenantData(string $tenantKey): void
    {
        // First check if a user with the same username already exists.
        $existingTenant = $this->tenants->findOneBy([self::TENANT_KEY_ARGUMENT => $tenantKey]);

        if (null !== $existingTenant) {
            throw new RuntimeException(sprintf('There is already a tenant registered with the "%s" key.', $tenantKey));
        }

        // Validate tenant key if is not this input means interactive.
        $this->validator->validateTenantKey($tenantKey);
    }

    /**
     * The command help is usually included in the configure() method.
     *
     * But when it's too long, it's better to define a separate method to maintain the code readability.
     */
    private function getCommandHelp(): string
    {
        return <<<'HELP'
The <info>%command.name%</info> command creates new tenants and saves them in the database:

  <info>php %command.full_name%</info> <comment>tenantKey</comment>

If you omit the tenantKey argument, the command will ask you to
provide the missing values:

  # command will ask you for the tenant key
  <info>php %command.full_name%</info> <comment>tenantKey</comment>

  # command will ask you for all arguments
  <info>php %command.full_name%</info>

HELP;
    }
}
