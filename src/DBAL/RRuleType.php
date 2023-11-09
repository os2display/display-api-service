<?php

namespace App\DBAL;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use RRule\RRule;

class RRuleType extends Type
{
    public const RRULE = 'rrule';

    /**
     * {@inheritDoc}
     */
    final public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getStringTypeDeclarationSQL($column);
    }

    /**
     * {@inheritDoc}
     */
    public function convertToPHPValue($value, AbstractPlatform $platform): ?RRule
    {
        if (null === $value) {
            return null;
        }

        return new RRule($value);
    }

    /**
     * {@inheritDoc}
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        /* @var RRule $value */
        return $value->rfcString(true);
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return self::RRULE;
    }

    /**
     * Because this Doctrine Type maps to an already mapped database type,
     * (StringType) reverse schema engineering can't tell them apart.
     * We need to mark this type as commented, which will have Doctrine use
     * an SQL comment to typehint the actual Doctrine Type (DC2Type:rrule).
     *
     * @param AbstractPlatform $platform
     *
     * @return bool
     */
    final public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
