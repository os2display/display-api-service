<?php

namespace App\Filter;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\AbstractContextAwareFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

class PublishedFilter extends AbstractContextAwareFilter
{
    private const FROM = 'from';
    private const TO = 'to';

    /**
     * {@inheritDoc}
     */
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null): void
    {
        if ('published' !== $property) {
            return;
        }

        // Validate and convert to boolean value.
        $published = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if (is_null($published)) {
            throw new InvalidArgumentException('The published filter value could not be recognized as a true or false boolean value');
        }

        // Validate that the properties on the entity is datetime fields.
        $properties = $this->getProperties();
        foreach ($properties as $property => $type) {
            if (!$this->isDateField($property, $resourceClass)) {
                $this->getLogger()->notice('Invalid filter ignored', [
                    'exception' => new InvalidArgumentException('Published filter property ('.$property.') is not a datetime doctrine field.'),
                ]);

                return;
            }

            // Validate that the property value is either 'form' or 'to'.
            if (!in_array($type, [self::FROM, self::TO])) {
                $this->getLogger()->notice('Invalid filter ignored', [
                    'exception' => new InvalidArgumentException('Published filter configuration should only contain from/to as service valye.'),
                ]);

                return;
            }
        }

        $types = array_values($properties);
        if (count($types) !== count(array_unique($types))) {
            $this->getLogger()->notice('Invalid filter ignored', [
                'exception' => new InvalidArgumentException('Published filter configuration should not contain the same value. Should contain one "from" and one "to".'),
            ]);
        }

        $alias = $queryBuilder->getRootAliases()[0];
        foreach ($properties as $property => $type) {
            switch ($type) {
                case self::FROM:
                    if ($published) {
                        $queryBuilder->andWhere(sprintf('%s.%s %s CURRENT_TIMESTAMP()', $alias, $property, '<='));
                    } else {
                        $queryBuilder->andWhere(sprintf('%s.%s %s CURRENT_TIMESTAMP()', $alias, $property, '>'));
                    }
                    break;

                case self::TO:
                    if ($published) {
                        $queryBuilder->andWhere(sprintf('%s.%s %s CURRENT_TIMESTAMP()', $alias, $property, '>='));
                    } else {
                        $queryBuilder->orWhere(sprintf('%s.%s %s CURRENT_TIMESTAMP()', $alias, $property, '<'));
                    }
                    break;
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription(string $resourceClass): array
    {
        // Published filter should have to properties 'from' and 'to' entity fields.
        if (!is_array($this->properties) || 2 !== count($this->properties)) {
            $this->getLogger()->notice('Invalid filter ignored', [
                'exception' => new InvalidArgumentException('Published filter should have two properties "from" and "to" entity datetime fields.'),
            ]);

            return [];
        }

        $description = [];
        $description['published'] = [
            'property' => 'published',
            'type' => Type::BUILTIN_TYPE_BOOL,
            'required' => false,
            'description' => 'If true only published content will be shown',
        ];

        return $description;
    }

    /**
     * Determines whether the given property refers to a date field.
     */
    private function isDateField(string $property, string $resourceClass): bool
    {
        return 'datetime' === $this->getDoctrineFieldType($property, $resourceClass);
    }
}
