<?php

namespace App\DataProvider;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Utils\ValidationUtils;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Security;

final class UserItemDataProvider implements ItemDataProviderInterface, RestrictedDataProviderInterface
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly ValidationUtils $validationUtils,
        private readonly Security $security,
        private readonly iterable $itemExtensions,
    ) {}

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return User::class === $resourceClass;
    }

    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = []): ?User
    {
        $ulid = $this->validationUtils->validateUlid($id);

        /** @var User $currentUser */
        $currentUser = $this->security->getUser();
        $activeTenant = $currentUser->getActiveTenant();
        $activeTenantUlid = $this->validationUtils->validateUlid($activeTenant->getId());

        $queryBuilder = $this->userRepository->getUsersByIdAndTenantQueryBuilder($ulid, $activeTenantUlid);

        try {
            $queryNameGenerator = new QueryNameGenerator();

            // Filter the query-builder with extensions.
            foreach ($this->itemExtensions as $extension) {
                $extension->applyToCollection($queryBuilder, $queryNameGenerator, $resourceClass, $operationName, $context);
            }
        } catch (AccessDeniedException) {
            throw new AccessDeniedHttpException();
        }

        try {
            return $queryBuilder->getQuery()->getOneOrNullResult();
        } catch (NonUniqueResultException) {
            return null;
        }
    }
}
