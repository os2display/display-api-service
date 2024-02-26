<?php

declare(strict_types=1);

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

class CampaignFilter extends AbstractFilter
{
    /**
     * {@inheritDoc}
     */
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if ('isCampaign' !== $property) {
            return;
        }
        $isCampaign = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if (is_null($isCampaign)) {
            throw new InvalidArgumentException('The is campaign filter value could not be recognized as a true or false boolean value');
        }
        $alias = $queryBuilder->getRootAliases()[0];
        $queryBuilder->andWhere(sprintf('%s.isCampaign = :isCampaign', $alias))->setParameter('isCampaign', $isCampaign);
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription(string $resourceClass): array
    {
        // Is campaign filter should have "isCampaign" entity boolean field.
        if (!is_array($this->properties) || 1 !== count($this->properties)) {
            $this->getLogger()->notice('Invalid filter ignored', [
                'exception' => new InvalidArgumentException('Is campaign filter should have one property "isCampaign" entity boolean field.'),
            ]);

            return [];
        }

        $description = [];
        $description['isCampaign'] = [
            'property' => 'isCampaign',
            'type' => Type::BUILTIN_TYPE_BOOL,
            'required' => false,
            'description' => 'If true only campaigns will be shown',
        ];

        return $description;
    }
}
