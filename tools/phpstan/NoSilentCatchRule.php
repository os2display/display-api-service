<?php

declare(strict_types=1);

namespace App\PhpStan;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Throw_;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Catch_;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use Psr\Log\LoggerInterface;

/**
 * Flags catch blocks that swallow an exception without logging or rethrowing.
 *
 * The rule's purpose is "do not hide a failure from an operator". A catch is
 * therefore NOT silent if its body does any of:
 *   - throws (rethrows the original or a wrapped exception);
 *   - calls a PSR-3 LoggerInterface method;
 *   - calls error_log()/trigger_error();
 *   - writes the failure to the console via a Symfony Console StyleInterface
 *     (e.g. $io->error(...)) or OutputInterface (e.g. $output->writeln(...)).
 *
 * The console-output exemption exists because a Command that prints the error
 * (and returns a non-zero exit code) has already surfaced the failure to the
 * operator running it — there is nothing hidden. This is handled in the rule,
 * not via a `src/Command/` path exclusion in phpstan.dist.neon, so that a
 * genuinely empty catch inside a command (which DOES hide a failure, e.g. in an
 * unattended cron/Messenger run) is still flagged.
 *
 * Intentional silent catches that fit none of the above must be annotated with
 * `@phpstan-ignore logging.silentCatch` plus a one-line justification.
 *
 * @implements Rule<Catch_>
 */
final class NoSilentCatchRule implements Rule
{
    /** @var list<string> */
    private const PSR3_METHODS = [
        'emergency', 'alert', 'critical', 'error',
        'warning', 'notice', 'info', 'debug', 'log',
    ];

    /** @var list<string> */
    private const PHP_ERROR_FUNCS = ['error_log', 'trigger_error'];

    /**
     * Symfony Console receivers whose output methods surface a message to the
     * operator running the command.
     *
     * @var list<string>
     */
    private const CONSOLE_OUTPUT_TYPES = [
        'Symfony\Component\Console\Style\StyleInterface',
        'Symfony\Component\Console\Output\OutputInterface',
    ];

    /**
     * Message-emitting methods on the console receivers above. Query/interaction
     * methods (isVerbose, ask, …) are deliberately excluded — they do not report
     * the failure.
     *
     * @var list<string>
     */
    private const CONSOLE_OUTPUT_METHODS = [
        'error', 'warning', 'caution', 'note', 'text', 'comment',
        'success', 'info', 'block', 'title', 'section', 'listing',
        'writeln', 'write',
    ];

    private readonly NodeFinder $nodeFinder;

    public function __construct()
    {
        $this->nodeFinder = new NodeFinder();
    }

    public function getNodeType(): string
    {
        return Catch_::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if ($this->rethrows($node)
            || $this->callsPhpErrorFunc($node)
            || $this->callsLogger($node, $scope)
            || $this->writesToConsole($node, $scope)
        ) {
            return [];
        }

        $caught = implode('|', array_map(static fn ($t): string => $t->toString(), $node->types));

        return [
            RuleErrorBuilder::message(sprintf(
                'Catch of %s does not log the exception or rethrow it. Silent catches hide '
                .'failures from operators. Log via Psr\\Log\\LoggerInterface (exception under '
                .'the "exception" context key), rethrow (optionally wrapped), or annotate with '
                .'`@phpstan-ignore logging.silentCatch` and a one-line justification.',
                '' !== $caught ? $caught : 'exception',
            ))
                ->identifier('logging.silentCatch')
                ->build(),
        ];
    }

    private function rethrows(Catch_ $node): bool
    {
        return [] !== $this->nodeFinder->findInstanceOf($node->stmts, Throw_::class);
    }

    private function callsPhpErrorFunc(Catch_ $node): bool
    {
        foreach ($this->nodeFinder->findInstanceOf($node->stmts, FuncCall::class) as $call) {
            if ($call->name instanceof Name && in_array($call->name->toLowerString(), self::PHP_ERROR_FUNCS, true)) {
                return true;
            }
        }

        return false;
    }

    private function callsLogger(Catch_ $node, Scope $scope): bool
    {
        $loggerType = new ObjectType(LoggerInterface::class);

        foreach ($this->nodeFinder->findInstanceOf($node->stmts, MethodCall::class) as $call) {
            if (!$call->name instanceof Node\Identifier) {
                continue;
            }
            if (!in_array(strtolower($call->name->name), self::PSR3_METHODS, true)) {
                continue;
            }
            if ($loggerType->isSuperTypeOf($scope->getType($call->var))->yes()) {
                return true;
            }
        }

        return false;
    }

    private function writesToConsole(Catch_ $node, Scope $scope): bool
    {
        $consoleTypes = array_map(static fn (string $t): ObjectType => new ObjectType($t), self::CONSOLE_OUTPUT_TYPES);

        foreach ($this->nodeFinder->findInstanceOf($node->stmts, MethodCall::class) as $call) {
            if (!$call->name instanceof Node\Identifier) {
                continue;
            }
            if (!in_array(strtolower($call->name->name), self::CONSOLE_OUTPUT_METHODS, true)) {
                continue;
            }
            $receiverType = $scope->getType($call->var);
            foreach ($consoleTypes as $consoleType) {
                if ($consoleType->isSuperTypeOf($receiverType)->yes()) {
                    return true;
                }
            }
        }

        return false;
    }
}
