<?php

declare(strict_types=1);

namespace App\Tests\PhpStan;

use App\PhpStan\NoSilentCatchRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<NoSilentCatchRule>
 */
class NoSilentCatchRuleTest extends RuleTestCase
{
    private const MESSAGE = 'Catch of %s does not log the exception or rethrow it. Silent catches hide '
        .'failures from operators. Log via Psr\Log\LoggerInterface (exception under '
        .'the "exception" context key), rethrow (optionally wrapped), or annotate with '
        .'`@phpstan-ignore logging.silentCatch` and a one-line justification.';

    protected function getRule(): Rule
    {
        return new NoSilentCatchRule();
    }

    public function testRule(): void
    {
        // Only an empty catch and a catch whose only statement is a non-reporting
        // console query are flagged; rethrow, wrapped rethrow, a PSR-3 logger
        // call, error_log() and a console output method are all exempt.
        $this->analyse([__DIR__.'/data/silent-catch.php'], [
            [sprintf(self::MESSAGE, 'RuntimeException'), 18],
            [sprintf(self::MESSAGE, 'RuntimeException'), 71],
        ]);
    }
}
