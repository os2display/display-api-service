<?php

namespace App\Command\User;

use App\Entity\Tenant;
use App\Enum\UserTypeEnum;
use App\Repository\TenantRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:user:set-user-type',
    description: 'Set user type for Users without type',
)]
class SetUserType extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
    }

    final protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $usersWithoutType = $this->userRepository->findAll();

        $io->writeln("" . count($usersWithoutType));

        foreach ($usersWithoutType as $user) {
            if (!empty($user->getUserType())) {
                continue;
            }

            if (!empty($user->getPassword())) {
                $user->setUserType(UserTypeEnum::USERNAME_PASSWORD);
            }
            else if ($user->getUserType() !== UserTypeEnum::OIDC_EXTERNAL && $user->getEmail() !== null) {
                $user->setUserType(UserTypeEnum::OIDC_ACTIVE_DIRECTORY);
            }
            else if ($user->getUserType() !== UserTypeEnum::OIDC_EXTERNAL) {
                $user->setUserType(UserTypeEnum::OIDC_EXTERNAL);
            }
        }

        $this->entityManager->flush();

        return Command::SUCCESS;
    }
}
