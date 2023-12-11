<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Paginator;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\PaginatorInterface;
use ApiPlatform\State\ProviderInterface;
use App\Dto\UserOutput;
use App\Entity\User;
use App\Entity\UserRoleTenant;
use App\Repository\UserRepository;
use App\Utils\ValidationUtils;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class UserProvider extends AbstractProvider
{
    public function __construct(
        ProviderInterface $collectionProvider,
        UserRepository $entityRepository,
        private readonly Security $security,
        private readonly ValidationUtils $validationUtils,
        private readonly iterable $itemExtensions,
        private readonly iterable $collectionExtensions,
        private readonly UserRepository $userRepository,
        private readonly RequestStack $requestStack,
    ) {
        parent::__construct($collectionProvider, $entityRepository);
    }

    protected function provideCollection(Operation $operation, array $uriVariables = [], array $context = []): PaginatorInterface
    {
        $currentRequest = $this->requestStack->getCurrentRequest();
        $itemsPerPage = $currentRequest->query?->get('itemsPerPage') ?? 10;
        $page = $currentRequest->query?->get('page') ?? 1;

        /** @var User $currentUser */
        $currentUser = $this->security->getUser();
        $activeTenant = $currentUser->getActiveTenant();
        $activeTenantUlid = $this->validationUtils->validateUlid($activeTenant->getId());

        $queryBuilder = $this->userRepository->getUsersByTenantQueryBuilder($activeTenantUlid);

        $resourceClass = User::class;
        $queryNameGenerator = new QueryNameGenerator();

        try {
            // Filter the query-builder with tenant extension.
            foreach ($this->collectionExtensions as $extension) {
                if ($extension instanceof QueryCollectionExtensionInterface) {
                    $extension->applyToCollection($queryBuilder, $queryNameGenerator, $resourceClass);
                }
            }
        } catch (AccessDeniedException) {
            throw new AccessDeniedHttpException();
        }

        $firstResult = ((int) $page - 1) * (int) $itemsPerPage;
        $query = $queryBuilder->getQuery()
            ->setFirstResult($firstResult)
            ->setMaxResults((int) $itemsPerPage);

        return new Paginator(new DoctrinePaginator($query));
    }

    protected function provideItem(Operation $operation, array $uriVariables = [], array $context = []): ?object
    {
        $id = $uriVariables['id'];
        $ulid = $this->validationUtils->validateUlid($id);

        /** @var User $currentUser */
        $currentUser = $this->security->getUser();
        $activeTenant = $currentUser->getActiveTenant();
        $activeTenantUlid = $this->validationUtils->validateUlid($activeTenant->getId());

        $queryBuilder = $this->userRepository->getUsersByIdAndTenantQueryBuilder($ulid, $activeTenantUlid);
        $resourceClass = User::class;
        $queryNameGenerator = new QueryNameGenerator();

        try {
            // Filter the query-builder with extensions.
            foreach ($this->itemExtensions as $extension) {
                if ($extension instanceof QueryItemExtensionInterface) {
                    $identifiers = ['id' => $id];
                    $extension->applyToItem($queryBuilder, $queryNameGenerator, $resourceClass, $identifiers, $operation, $context);
                }
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

    public function toOutput(object $object): UserOutput
    {
        assert($object instanceof User);

        $output = new UserOutput();
        $output->id = $object->getId();
        $output->fullName = $object->getFullName();
        $output->userType = $object->getUserType();
        $output->createdAt = $object->getCreatedAt();

        /** @var User $currentUser */
        $currentUser = $this->security->getUser();
        $activeTenant = $currentUser->getActiveTenant();

        $output->roles = array_unique(array_reduce($object->getUserRoleTenants()->toArray(), function ($carry, UserRoleTenant $userRoleTenant) use ($activeTenant) {
            if ($userRoleTenant->getTenant() === $activeTenant) {
                $carry += $userRoleTenant->getRoles();
            }

            return $carry;
        }, []));

        return $output;
    }
}
