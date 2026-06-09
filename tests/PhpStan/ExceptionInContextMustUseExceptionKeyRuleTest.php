<?php

declare(strict_types=1);

namespace App\Tests\PhpStan;

use App\PhpStan\ExceptionInContextMustUseExceptionKeyRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<ExceptionInContextMustUseExceptionKeyRule>
 */
class ExceptionInContextMustUseExceptionKeyRuleTest extends RuleTestCase
{
    private const MESSAGE = 'A Throwable is logged under the "%s" context key; use the "exception" key '
        .'so it is serialised consistently (and picked up by ExceptionContextProcessor).';

    protected function getRule(): Rule
    {
        return new ExceptionInContextMustUseExceptionKeyRule();
    }

    public function testRule(): void
    {
        // A Throwable under any context key other than `exception` is flagged;
        // the `exception` key (including in the log() signature) and non-throwable
        // values are allowed.
        $this->analyse([__DIR__.'/data/exception-context-key.php'], [
            [sprintf(self::MESSAGE, 'error'), 16],
            [sprintf(self::MESSAGE, 'cause'), 21],
        ]);
    }
}
