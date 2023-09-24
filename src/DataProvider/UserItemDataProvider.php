<?php

namespace App\DataProvider;

use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Utils\ValidationUtils;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\Security\Core\Security;

final class UserItemDataProvider implements ItemDataProviderInterface, RestrictedDataProviderInterface
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly ValidationUtils $validationUtils,
        private readonly Security $security,
    ) {}

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return User::class === $resourceClass;
    }

    /**
     * @throws NonUniqueResultException
     */
    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = []): ?User
    {
        $ulid = $this->validationUtils->validateUlid($id);

        /** @var User $currentUser */
        $currentUser = $this->security->getUser();
        $activeTenant = $currentUser->getActiveTenant();
        $activeTenantUlid = $this->validationUtils->validateUlid($activeTenant->getId());

        return $this->userRepository->getExternalUserByIdAndTenant($ulid, $activeTenantUlid);
    }
}
