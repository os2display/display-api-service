<?php

declare(strict_types=1);

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class ThemeCss extends Constraint
{
    // Violation codes, the same as the admin's theme css lint reports.
    public const string UNTERMINATED_COMMENT = 'unterminated_comment';
    public const string UNTERMINATED_STRING = 'unterminated_string';
    public const string UNEXPECTED_BRACE = 'unexpected_brace';
    public const string UNCLOSED_BRACE = 'unclosed_brace';

    public string $unterminatedCommentMessage = 'The css has a comment that is not closed: add "*/" after "{{ excerpt }}".';
    public string $unterminatedStringMessage = 'The css has a string that is not closed: {{ excerpt }}';
    public string $unexpectedBraceMessage = 'The css has a "}" on line {{ line }} with no matching "{".';
    public string $unclosedBraceMessage = 'The css has a "{" on line {{ line }} that is never closed.';

    final public function validatedBy(): string
    {
        return ThemeCssValidator::class;
    }
}
