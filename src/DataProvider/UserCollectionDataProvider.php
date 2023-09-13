<?php

namespace App\DataProvider;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Paginator;
use ApiPlatform\Core\DataProvider\ContextAwareCollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use App\Entity\User;
use App\Enum\UserTypeEnum;
use App\Repository\UserRepository;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;
use Symfony\Component\HttpFoundation\RequestStack;

final class UserCollectionDataProvider implements ContextAwareCollectionDataProviderInterface, RestrictedDataProviderInterface
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly UserRepository $userRepository,
    ) {}

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return User::class === $resourceClass && 'get' === $operationName;
    }

    public function getCollection(string $resourceClass, string $operationName = null, array $context = []): Paginator
    {
        $currentRequest = $this->requestStack->getCurrentRequest();
        $itemsPerPage = $currentRequest->query?->get('itemsPerPage') ?? 10;
        $page = $currentRequest->query?->get('page') ?? 1;

        // Get playlist to check shared-with-tenants
        $queryBuilder = $this->userRepository->createQueryBuilder('user');

        // Apply that userType should match UserTypeEnum::OIDC_EXTERNAL for external-users endpoints.
        if (str_starts_with($context['request_uri'] ?? '', '/v1/external-users')) {
            $queryBuilder->andWhere('user.userType = :type')->setParameter('type', UserTypeEnum::OIDC_EXTERNAL);
        }

        // TODO: Require that users are members of the user's tenant.

        $firstResult = ((int) $page - 1) * (int) $itemsPerPage;
        $query = $queryBuilder->getQuery()
            ->setFirstResult($firstResult)
            ->setMaxResults((int) $itemsPerPage);

        $doctrinePaginator = new DoctrinePaginator($query);

        return new Paginator($doctrinePaginator);
    }
}
