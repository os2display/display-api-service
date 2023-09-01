<?php

namespace App\DataPersister;

use ApiPlatform\Core\DataPersister\ContextAwareDataPersisterInterface;
use App\Entity\User;
use App\Entity\UserRoleTenant;
use App\Exceptions\NoUserException;
use App\Exceptions\UserTypeNotSupportedException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;

final class UserDataPersister implements ContextAwareDataPersisterInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly Security $security,
    )
    {
    }

    public function supports($data, array $context = []): bool
    {
        return $data instanceof User;
    }

    /**
     * @throws UserTypeNotSupportedException
     * @var User $data
     */
    public function persist($data, array $context = []): User
    {
        /** @var User $user */
        $user = $this->security->getUser();

        if (is_null($user)) {
            return throw new NoUserException();
        }

        // Only handle external-users endpoints.
        if (!str_starts_with($context['request_uri'] ?? '', '/v1/external-users')) {
            throw new UserTypeNotSupportedException();
        }

        $userRoleTenant = new UserRoleTenant();
        $userRoleTenant->setUser($data);
        $userRoleTenant->setTenant($user->getActiveTenant());
        // TODO: Handle ROLE_EXTERNAL_USER_ADMIN.
        $userRoleTenant->setRoles(["ROLE_EXTERNAL_USER"]);
        $data->addUserRoleTenant($userRoleTenant);
        $this->entityManager->persist($userRoleTenant);

        $this->entityManager->persist($data);
        $this->entityManager->flush();

        return $data;
    }

    public function remove($data, array $context = [])
    {
        // Only handle external-users endpoints.
        if (str_starts_with($context['request_uri'] ?? '', '/v1/external-users')) {
            throw new UserTypeNotSupportedException();
        }

        $this->entityManager->remove($data);
        $this->entityManager->flush();
    }
}
