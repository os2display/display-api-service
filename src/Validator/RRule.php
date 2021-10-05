<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class RRule extends Constraint
{
    public string $message = 'The string "{{ string }}" is not af valid RFC 5545 string: {{ error }}';

    /**
     * {@inheritDoc}
     */
    final public function validatedBy(): string
    {
        return RRuleValidator::class;
    }
}
