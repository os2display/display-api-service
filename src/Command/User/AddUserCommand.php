<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command\User;

use App\Entity\Tenant;
use App\Entity\User;
use App\Entity\UserRoleTenant;
use App\Exceptions\AddUserCommandException;
use App\Exceptions\EntityException;
use App\Repository\TenantRepository;
use App\Repository\UserRepository;
use App\Utils\CommandInputValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Stopwatch\Stopwatch;

use function Symfony\Component\String\u;

/**
 * A console command that creates users and stores them in the database.
 *
 * To use this command, open a terminal window, enter into your project
 * directory and execute the following:
 *
 *     $ php bin/console app:user:add
 *
 * To output detailed information, increase the command verbosity:
 *
 *     $ php bin/console app:user:add -vv
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
    name: 'app:user:add',
    description: 'Creates users and stores them in the database'
)]
class AddUserCommand extends Command
{
    private const EMAIL_ARGUMENT = 'email';
    private const PASSWORD_ARGUMENT = 'password';
    private const FULL_NAME_ARGUMENT = 'full-name';
    private const ROLE_ARGUMENT = 'role';
    private const TENANT_KEYS_ARGUMENT = 'tenant-keys';

    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private CommandInputValidator $validator,
        private UserRepository $users,
        private TenantRepository $tenantRepository
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
            ->addArgument(self::EMAIL_ARGUMENT, InputArgument::OPTIONAL, 'The email of the new user')
            ->addArgument(self::PASSWORD_ARGUMENT, InputArgument::OPTIONAL, 'The plain password of the new user')
            ->addArgument(self::FULL_NAME_ARGUMENT, InputArgument::OPTIONAL, 'The full name of the new user')
            ->addArgument(self::ROLE_ARGUMENT, InputArgument::OPTIONAL, 'The role of the user [editor|admin]')
            ->addArgument(self::TENANT_KEYS_ARGUMENT, InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'The keys of the tenants the user should belong to (separate multiple keys with a space)')
        ;
    }

    /**
     * This method is executed after initialize() and before execute(). Its purpose
     * is to check if some of the options/arguments are missing and interactively
     * ask the user for those values.
     *
     * This method is completely optional. If you are developing an internal console
     * command, you probably should not implement this method because it requires
     * quite a lot of work. However, if the command is meant to be used by external
     * users, this method is a nice way to fall back and prevent errors.
     */
    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        if (null !== $input->getArgument(self::EMAIL_ARGUMENT) &&
            null !== $input->getArgument(self::PASSWORD_ARGUMENT) &&
            null !== $input->getArgument(self::FULL_NAME_ARGUMENT) &&
            null !== $input->getArgument(self::ROLE_ARGUMENT) &&
            !empty($input->getArgument(self::TENANT_KEYS_ARGUMENT))
        ) {
            return;
        }

        $io = new SymfonyStyle($input, $output);

        $io->title('Add User Command Interactive Wizard');
        $io->text([
            'If you prefer to not use this interactive wizard, provide the',
            'arguments required by this command as follows:',
            '',
            ' $ php bin/console app:user:add email@example.com password',
            '',
            'Now we\'ll ask you for the value of all the missing command arguments.',
        ]);

        // Ask for the email if it's not defined
        $email = $input->getArgument(self::EMAIL_ARGUMENT);
        if (null !== $email) {
            $io->text(' > <info>Email</info>: '.$email);
        } else {
            $email = $io->ask('Email', null, [$this->validator, 'validateEmail']);
            $input->setArgument(self::EMAIL_ARGUMENT, $email);
        }

        // Ask for the password if it's not defined
        $password = $input->getArgument(self::PASSWORD_ARGUMENT);
        if (null !== $password) {
            $io->text(' > <info>Password</info>: '.u('*')->repeat(u($password)->length()));
        } else {
            $password = $io->askHidden('Password (your type will be hidden)', [$this->validator, 'validatePassword']);
            $input->setArgument(self::PASSWORD_ARGUMENT, $password);
        }

        // Ask for the full name if it's not defined
        $fullName = $input->getArgument(self::FULL_NAME_ARGUMENT);
        if (null !== $fullName) {
            $io->text(' > <info>Full Name</info>: '.$fullName);
        } else {
            $fullName = $io->ask('Full Name', null, [$this->validator, 'validateFullName']);
            $input->setArgument(self::FULL_NAME_ARGUMENT, $fullName);
        }

        $helper = $this->getHelper('question');

        // Ask for the role if it's not defined
        $role = $input->getArgument(self::ROLE_ARGUMENT);
        if (null !== $role) {
            $io->text(' > <info>Role</info>: '.$role);
        } else {
            $question = new ChoiceQuestion(
                'Please select the user\'s role (defaults to editor)',
                CommandInputValidator::ALLOWED_USER_ROLES,
                0
            );
            $question->setErrorMessage('Role %s is invalid.');

            $role = $helper->ask($input, $output, $question);
            $output->writeln('You have just selected: '.$role);
            $input->setArgument(self::ROLE_ARGUMENT, $role);
        }

        // Ask for the tenant keys if it's not defined
        $tenantKeys = $input->getArgument(self::TENANT_KEYS_ARGUMENT);
        if (0 < count($tenantKeys)) {
            $io->text(' > <info>Tenant Keys</info>: '.implode(', ', $tenantKeys));
        } else {
            $question = new ChoiceQuestion(
                'Please select the tenant(s) the user should belong to (to select multiple answer with a list. E.g: "key1, key3")',
                $this->getTenantsChoiceList(),
            );
            $question->setMultiselect(true);

            $tenantKeys = $helper->ask($input, $output, $question);
            $output->writeln('You have just selected: '.implode(', ', $tenantKeys));
            $input->setArgument(self::TENANT_KEYS_ARGUMENT, $tenantKeys);
        }
    }

    /**
     * This method is executed after interact() and initialize(). It usually
     * contains the logic to execute to complete this command task.
     *
     * @throws AddUserCommandException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $stopwatch = new Stopwatch();
        $stopwatch->start('add-user-command');

        // All arguments should now be set
        $email = $this->getArgumentFromInput($input, self::EMAIL_ARGUMENT);
        $plainPassword = $this->getArgumentFromInput($input, self::PASSWORD_ARGUMENT);
        $fullName = $this->getArgumentFromInput($input, self::FULL_NAME_ARGUMENT);
        $role = $this->getArgumentFromInput($input, self::ROLE_ARGUMENT);
        $tenantKeys = $this->getArgumentFromInput($input, self::TENANT_KEYS_ARGUMENT);

        // make sure to validate the user data is correct
        $this->validateUserData($email, $plainPassword, $fullName, $role, $tenantKeys);

        // create the user and hash its password
        $user = new User();
        $user->setEmail($email);
        $user->setFullName($fullName);
        $user->setProvider(self::class);
        $user->setCreatedBy('CLI');

        // See https://symfony.com/doc/5.4/security.html#registering-the-user-hashing-passwords
        $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashedPassword);

        foreach ($tenantKeys as $tenantKey) {
            $tenant = $this->tenantRepository->findOneBy(['tenantKey' => $tenantKey]);
            $userRoleTenant = new UserRoleTenant();
            $userRoleTenant->setTenant($tenant);
            $userRoleTenant->setRoles(['ROLE_'.strtoupper($role)]);
            $user->addUserRoleTenant($userRoleTenant);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success(sprintf('%s was successfully created: %s', ucfirst($role), $user->getUserIdentifier()));

        $event = $stopwatch->stop('add-user-command');
        if ($output->isVerbose()) {

            $userId = $user->getId();

            if (null === $userId) {
                throw new EntityException('User id null');
            }

            $io->comment(sprintf('New user database id: %s / Elapsed time: %.2f ms / Consumed memory: %.2f MB', $userId->jsonSerialize(), $event->getDuration(), $event->getMemory() / (1024 ** 2)));
        }

        return Command::SUCCESS;
    }

    private function validateUserData(string $email, string $plainPassword, string $fullName, string $role, array $tenantKeys): void
    {
        // first check if a user with the same username already exists.
        $existingUser = $this->users->findOneBy([self::EMAIL_ARGUMENT => $email]);

        if (null !== $existingUser) {
            throw new RuntimeException(sprintf('There is already a user registered with the "%s" email.', $email));
        }

        // validate password and email if is not this input means interactive.
        $this->validator->validatePassword($plainPassword);
        $this->validator->validateEmail($email);
        $this->validator->validateFullName($fullName);
        $this->validator->validateRole($role);
        $this->validator->validateTenantKeys($tenantKeys);
    }

    private function getTenantsChoiceList(): array
    {
        $tenants = [];
        foreach ($this->tenantRepository->findBy([], ['tenantKey' => 'ASC']) as $tenant) {
            $tenants[$tenant->getTenantKey()] = $tenant->getDescription();
        }

        return $tenants;
    }

    /**
     * The command help is usually included in the configure() method, but when
     * it's too long, it's better to define a separate method to maintain the
     * code readability.
     */
    private function getCommandHelp(): string
    {
        return <<<'HELP'
The <info>%command.name%</info> command creates new users and saves them in the database:

  <info>php %command.full_name%</info> <comment>email password fullname role tenants</comment>

If you omit any of the required arguments, the command will ask you to
provide the missing values:

  # command will ask you for the password etc.
  <info>php %command.full_name%</info> <comment>email</comment>

  # command will ask you for all arguments
  <info>php %command.full_name%</info>

HELP;
    }

    /**
     * Gets argument from input or throws exception.
     *
     * @throws AddUserCommandException
     */
    private function getArgumentFromInput(InputInterface $input, string $name): mixed
    {
        $argument = $input->getArgument($name);

        if (null === $argument) {
            throw new AddUserCommandException(sprintf('Cannot create user with missing argument: %s', $name));
        }

        return $argument;
    }
}
