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
 * Catches what breaks every screen showing a theme rather than a single rule:
 * an unterminated comment (which hung the client, #572), an unterminated string
 * and unbalanced braces, plus constructs that run code or load bindings. It is
 * not a css parser: unknown properties or invalid values are left to the
 * browser, which ignores them.
 */
class ThemeCssValidator extends ConstraintValidator
{
    private const array FORBIDDEN = ['expression(', '-moz-binding', 'javascript:'];

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

        $opened = 0;
        $closed = 0;
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
                    ++$opened;
                } elseif ('}' === $value[$i]) {
                    ++$closed;
                }
                ++$i;
            }
        }

        if ($opened !== $closed) {
            $this->context->buildViolation($constraint->unbalancedBracesMessage)
                ->setParameter('{{ opened }}', (string) $opened)
                ->setParameter('{{ closed }}', (string) $closed)
                ->addViolation();

            return;
        }

        $lower = strtolower($value);
        foreach (self::FORBIDDEN as $construct) {
            if (str_contains($lower, $construct)) {
                $this->context->buildViolation($constraint->forbiddenMessage)
                    ->setParameter('{{ construct }}', $construct)
                    ->addViolation();

                return;
            }
        }
    }

    private static function excerpt(string $css, int $offset): string
    {
        return trim(mb_strimwidth(substr($css, $offset), 0, 40, '…'));
    }
}
