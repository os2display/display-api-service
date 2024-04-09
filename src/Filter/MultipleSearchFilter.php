<?php

declare(strict_types=1);

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;

class MultipleSearchFilter extends AbstractFilter
{
    /**
     * {@inheritDoc}
     */
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
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
