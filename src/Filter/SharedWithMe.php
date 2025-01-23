<?php

declare(strict_types=1);

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\Operation;
use App\Entity\User;
use App\Exceptions\EntityException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

class SharedWithMe extends AbstractFilter
{
    public function __construct(
        ManagerRegistry $managerRegistry,
        private readonly Security $security,
        ?LoggerInterface $logger = null,
        array $properties = [],
        ?NameConverterInterface $nameConverter = null,
    ) {
        parent::__construct($managerRegistry, $logger, $properties, $nameConverter);
    }

    /**
     * {@inheritDoc}
     */
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if ('sharedWithMe' !== $property) {
            return;
        }

        $user = $this->security->getUser();
        if (is_null($user)) {
            return;
        }

        /** @var User $user */
        $tenant = $user->getActiveTenant();
        $isSharedWithMe = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if (is_null($isSharedWithMe)) {
            throw new InvalidArgumentException('The is-shared-with-me filter value could not be recognized as a true or false boolean value');
        }

        $tenantId = $tenant->getId();

        if (null === $tenantId) {
            throw new EntityException('Tenant id is null');
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];
        if ($isSharedWithMe) {
            $queryBuilder->andWhere(sprintf(':tenant MEMBER OF %s.tenants', $rootAlias))
          ->setParameter('tenant', $tenantId->toBinary());
        } else {
            $queryBuilder->andWhere(sprintf('%s.tenant = :tenant', $rootAlias))
          ->setParameter('tenant', $tenantId->toBinary());
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription(string $resourceClass): array
    {
        // Is shared with me filter should have "shared with me" entity boolean field.
        if (!is_array($this->properties) || 1 !== count($this->properties)) {
            $this->getLogger()->notice('Invalid filter ignored', [
                'exception' => new InvalidArgumentException('Is shared with me filter should have one property "sharedWithMe" entity boolean field.'),
            ]);

            return [];
        }

        $description = [];
        $description['sharedWithMe'] = [
            'property' => 'sharedWithMe',
            'type' => Type::BUILTIN_TYPE_BOOL,
            'required' => false,
            'description' => 'If true only entities that are shared with me will be shown',
        ];

        return $description;
    }
}
