<?php

declare(strict_types=1);

namespace App\Tests\PhpStan;

use App\PhpStan\NoInterpolatedLogMessageRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<NoInterpolatedLogMessageRule>
 */
class NoInterpolatedLogMessageRuleTest extends RuleTestCase
{
    private const MESSAGE = 'Log message must be a static string; put variable data in the context '
        .'array instead (use "{placeholder}" braces resolved from context if you '
        .'need it inside the message).';

    protected function getRule(): Rule
    {
        return new NoInterpolatedLogMessageRule();
    }

    public function testRule(): void
    {
        // Interpolated double-quoted strings and concats involving a variable are
        // flagged (including the message-as-second-arg log() signature); static
        // strings, "{placeholder}" messages and const-only concats are allowed.
        $this->analyse([__DIR__.'/data/interpolated-log.php'], [
            [self::MESSAGE, 13],
            [self::MESSAGE, 18],
            [self::MESSAGE, 23],
        ]);
    }
}
