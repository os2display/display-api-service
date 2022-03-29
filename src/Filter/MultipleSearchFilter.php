<?php

namespace App\Filter;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\AbstractContextAwareFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use Doctrine\ORM\QueryBuilder;

class MultipleSearchFilter extends AbstractContextAwareFilter
{
    /**
     * {@inheritDoc}
     */
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null): void
    {
        if ('search' !== $property) {
            return;
        }
        if (empty($value)) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];
        $queryBuilder->andWhere(sprintf('%s.title LIKE :search OR %s.location LIKE :search', $alias, $alias))->setParameter('search', '%'.$value.'%');
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription(string $resourceClass): array
    {
        // Is search filter should have "search" entity boolean field.
        if (!is_array($this->properties) || 1 !== count($this->properties)) {
            $this->getLogger()->notice('Invalid filter ignored', [
                'exception' => new InvalidArgumentException('Is search filter should have one property "search" entity field.'),
            ]);

            return [];
        }

        $description = [];
        $description['search'] = [
            'property' => 'search',
            'type' => 'string',
            'required' => false,
            'description' => 'Search on both location and title',
        ];

        return $description;
    }
}
