<?php

declare(strict_types=1);

namespace App\PhpStan;

use PhpParser\Node;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Scalar\InterpolatedString;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use Psr\Log\LoggerInterface;

/**
 * Flags dynamic log messages on PSR-3 LoggerInterface calls.
 *
 * The first argument of a logger method must be a STATIC string. Variable data
 * belongs in the context array (optionally referenced with `{placeholder}`
 * braces). A double-quoted/heredoc interpolation or a string concatenation
 * involving a variable is flagged.
 *
 * @implements Rule<MethodCall>
 */
final class NoInterpolatedLogMessageRule implements Rule
{
    /** @var list<string> */
    private const PSR3_METHODS = [
        'emergency', 'alert', 'critical', 'error',
        'warning', 'notice', 'info', 'debug', 'log',
    ];

    private readonly NodeFinder $nodeFinder;

    public function __construct()
    {
        $this->nodeFinder = new NodeFinder();
    }

    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (!$this->isPsr3LoggerCall($node, $scope)) {
            return [];
        }

        // `log()` takes the level first, the message second; the others take the
        // message first.
        $messageIndex = ($node->name instanceof Node\Identifier && 'log' === strtolower($node->name->name)) ? 1 : 0;

        $args = $node->getArgs();
        if (!isset($args[$messageIndex])) {
            return [];
        }

        $message = $args[$messageIndex]->value;

        if ($message instanceof InterpolatedString || $this->isConcatWithVariable($message)) {
            return [
                RuleErrorBuilder::message(
                    'Log message must be a static string; put variable data in the context '
                    .'array instead (use "{placeholder}" braces resolved from context if you '
                    .'need it inside the message).',
                )
                    ->identifier('logging.interpolatedLogMessage')
                    ->build(),
            ];
        }

        return [];
    }

    private function isConcatWithVariable(Node $message): bool
    {
        if (!$message instanceof Concat) {
            return false;
        }

        return [] !== $this->nodeFinder->findInstanceOf([$message], Variable::class);
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
