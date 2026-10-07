<?php

declare(strict_types=1);

namespace App\PhpStan;

use PhpParser\Node;
use PhpParser\Node\ArrayItem;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use Psr\Log\LoggerInterface;

/**
 * Flags a `\Throwable` passed in a PSR-3 logger context array under any string
 * key other than `exception`.
 *
 * The single `exception` key is the convention the ExceptionContextProcessor
 * relies on to serialise throwables; an exception under e.g. `error` would be
 * left as an opaque object.
 *
 * @implements Rule<MethodCall>
 */
final class ExceptionInContextMustUseExceptionKeyRule implements Rule
{
    /** @var list<string> */
    private const PSR3_METHODS = [
        'emergency', 'alert', 'critical', 'error',
        'warning', 'notice', 'info', 'debug', 'log',
    ];

    public function processNode(Node $node, Scope $scope): array
    {
        if (!$this->isPsr3LoggerCall($node, $scope)) {
            return [];
        }

        $throwableType = new ObjectType(\Throwable::class);
        $errors = [];

        foreach ($this->contextArrays($node) as $context) {
            foreach ($context->items as $item) {
                if (!$item instanceof ArrayItem || !$item->key instanceof String_) {
                    continue;
                }
                if ('exception' === $item->key->value) {
                    continue;
                }
                if (!$throwableType->isSuperTypeOf($scope->getType($item->value))->yes()) {
                    continue;
                }

                $errors[] = RuleErrorBuilder::message(sprintf(
                    'A Throwable is logged under the "%s" context key; use the "exception" key '
                    .'so it is serialised consistently (and picked up by ExceptionContextProcessor).',
                    $item->key->value,
                ))
                    ->identifier('logging.exceptionContextKey')
                    ->build();
            }
        }

        return $errors;
    }

    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    /**
     * Returns the context-array arguments of the call (the last argument of each
     * PSR-3 signature).
     *
     * @return list<Array_>
     */
    private function contextArrays(MethodCall $node): array
    {
        $args = $node->getArgs();
        $arrays = [];
        foreach ($args as $arg) {
            if ($arg->value instanceof Array_) {
                $arrays[] = $arg->value;
            }
        }

        return $arrays;
    }

    private function isPsr3LoggerCall(MethodCall $node, Scope $scope): bool
    {
        if (!$node->name instanceof Node\Identifier) {
            return false;
        }
        if (!in_array(strtolower($node->name->name), self::PSR3_METHODS, true)) {
            return false;
        }

        return (new ObjectType(LoggerInterface::class))->isSuperTypeOf($scope->getType($node->var))->yes();
    }
}
