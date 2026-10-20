<?php

declare(strict_types=1);

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class ThemeCss extends Constraint
{
    public string $unterminatedCommentMessage = 'The css has a comment that is not closed: add "*/" after "{{ excerpt }}".';
    public string $unterminatedStringMessage = 'The css has a string that is not closed: {{ excerpt }}';
    public string $unbalancedBracesMessage = 'The css braces do not match: {{ opened }} "{" and {{ closed }} "}".';
    public string $forbiddenMessage = 'The css must not use "{{ construct }}".';

    final public function validatedBy(): string
    {
        return ThemeCssValidator::class;
    }
}
