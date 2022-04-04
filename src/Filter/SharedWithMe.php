<?php

namespace App\Filter;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\AbstractContextAwareFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

class SharedWithMe extends AbstractContextAwareFilter
{
    private $security;

    public function __construct(
        ManagerRegistry $managerRegistry, ?RequestStack $requestStack = null, LoggerInterface $logger = null, array $properties = [], NameConverterInterface $nameConverter = null, Security $security
    ) {
        parent::__construct($managerRegistry, $requestStack, $logger, $properties, $nameConverter);
        $this->security = $security;
    }

    /**
     * {@inheritDoc}
     */
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null): void
    {
        if ('sharedWithMe' !== $property) {
            return;
        }

        if (null === $user = $this->security->getUser()) {
            return;
        }

        $tenant = $user->getActiveTenant();
        $isSharedWithMe = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if (is_null($isSharedWithMe)) {
            throw new InvalidArgumentException('The is-shared-with-me filter value could not be recognized as a true or false boolean value');
        }
        $rootAlias = $queryBuilder->getRootAliases()[0];
        if ($isSharedWithMe) {
            $queryBuilder->andWhere(sprintf(':tenant MEMBER OF %s.tenants', $rootAlias))
          ->setParameter('tenant', $tenant->getId()->toBinary());
        } else {
            $queryBuilder->andWhere(sprintf('%s.tenant = :tenant', $rootAlias))
          ->setParameter('tenant', $tenant->getId()->toBinary());
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription(string $resourceClass): array
    {
        // Is is shared with me filter should have "shared with me" entity boolean field.
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
