<?php

namespace App\DataProvider;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use App\Entity\Tenant\Theme;
use App\Entity\User;
use App\Enum\UserTypeEnum;
use App\Repository\SlideRepository;
use App\Repository\ThemeRepository;
use App\Repository\UserRepository;
use App\Utils\ValidationUtils;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\Security\Core\Security;

final class UserItemDataProvider implements ItemDataProviderInterface, RestrictedDataProviderInterface
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly ValidationUtils $validationUtils,
    ) {}

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return User::class === $resourceClass;
    }

    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = []): ?User
    {
        $ulid = $this->validationUtils->validateUlid($id);

        // Create a query-builder, as the tenant filter works on query-builders.
        $queryBuilder = $this->userRepository->getById($ulid);

        // Apply that userType should match UserTypeEnum::OIDC_EXTERNAL for external-users endpoints.
        if (str_starts_with($context['request_uri'] ?? '', '/v1/external-users')) {
            $queryBuilder->andWhere('u.userType = :type')->setParameter('type', UserTypeEnum::OIDC_EXTERNAL);
        }

        // TODO: Require that the user is a member of the user's tenant.

        // Get result. If there is a result this is returned.
        try {
            $user = $queryBuilder->getQuery()->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            $user = null;
        }

        return $user;
    }
}
