<?php

declare(strict_types=1);

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * Structural checks on theme css, enforced when a theme is saved.
 *
 * Catches an unterminated comment (which hung every screen showing the theme,
 * #572), an unterminated string and unbalanced braces (which break the theme's
 * own rules). It is not a css parser: unknown properties or invalid values are
 * left to the browser, which ignores them. It is no security check either: the
 * css is rendered as text in a <style> element.
 */
class ThemeCssValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ThemeCss) {
            throw new UnexpectedTypeException($constraint, ThemeCss::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!\is_string($value)) {
            throw new UnexpectedValueException($value, 'string');
        }

        // Offsets of the "{" still open, so a "}" with nothing to close is
        // caught where it is, not only when the totals differ.
        $open = [];
        $length = \strlen($value);
        $i = 0;
        while ($i < $length) {
            if (str_starts_with(substr($value, $i, 2), '/*')) {
                $end = strpos($value, '*/', $i + 2);
                if (false === $end) {
                    $this->context->buildViolation($constraint->unterminatedCommentMessage)
                        ->setParameter('{{ excerpt }}', self::excerpt($value, $i))
                        ->addViolation();

                    return;
                }
                $i = $end + 2;
            } elseif ('"' === $value[$i] || "'" === $value[$i]) {
                // A css string ends at its closing quote; a raw newline ends
                // it unclosed. A backslash escapes the next character.
                $quote = $value[$i];
                $j = $i + 1;
                while ($j < $length && $quote !== $value[$j] && "\n" !== $value[$j]) {
                    $j += '\\' === $value[$j] ? 2 : 1;
                }
                if ($j >= $length || $quote !== $value[$j]) {
                    $this->context->buildViolation($constraint->unterminatedStringMessage)
                        ->setParameter('{{ excerpt }}', self::excerpt($value, $i))
                        ->addViolation();

                    return;
                }
                $i = $j + 1;
            } else {
                if ('{' === $value[$i]) {
                    $open[] = $i;
                } elseif ('}' === $value[$i]) {
                    if ([] === $open) {
                        $this->context->buildViolation($constraint->unexpectedBraceMessage)
                            ->setParameter('{{ line }}', (string) self::line($value, $i))
                            ->addViolation();

                        return;
                    }
                    array_pop($open);
                }
                ++$i;
            }
        }

        if ([] !== $open) {
            $this->context->buildViolation($constraint->unclosedBraceMessage)
                ->setParameter('{{ line }}', (string) self::line($value, end($open)))
                ->addViolation();

            return;
        }
    }

    private static function line(string $css, int $offset): int
    {
        return substr_count($css, "\n", 0, $offset) + 1;
    }

    private static function excerpt(string $css, int $offset): string
    {
        return trim(mb_strimwidth(substr($css, $offset), 0, 40, '…'));
    }
}
