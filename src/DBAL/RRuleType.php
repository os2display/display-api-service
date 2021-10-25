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
    public function getSQLDeclaration(array $column, AbstractPlatform $platform)
    {
        return $platform->getVarcharTypeDeclarationSQL($column);
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
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
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
     * {@inheritdoc}
     */
    public function requiresSQLCommentHint(AbstractPlatform $platform)
    {
        return true;
    }
}
