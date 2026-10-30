<?php

declare(strict_types=1);

namespace App\Tests\Api\FlushCount;

use App\Tests\AbstractBaseApiTestCase;
use App\Tests\Probe\CalculatorProbe;
use App\Tests\Probe\FlushCounter;

/**
 * Characterization tests pinning flush and checksum calculator counts per write endpoint (#281).
 *
 * One test file per endpoint, so a fix for one endpoint only flips the expectation in its own file.
 */
abstract class AbstractFlushCountTestCase extends AbstractBaseApiTestCase
{
    /**
     * Reset the probes in the current kernel's container. Call right before the request under test.
     */
    protected function resetProbes(): void
    {
        static::getContainer()->get(FlushCounter::class)->reset();
        static::getContainer()->get(CalculatorProbe::class)->reset();
    }

    /**
     * @param int[] $nestingLevels expected transaction nesting level at each calculator execute() call
     */
    protected function assertProbes(int $flushes, array $nestingLevels): void
    {
        $flushCounter = static::getContainer()->get(FlushCounter::class);
        $calculatorProbe = static::getContainer()->get(CalculatorProbe::class);

        // Compare as one array so a failure shows all three values.
        $this->assertSame(
            [
                'flushes' => $flushes,
                'calculatorInvocations' => count($nestingLevels),
                'nestingLevelsAtExecute' => $nestingLevels,
            ],
            [
                'flushes' => $flushCounter->count(),
                'calculatorInvocations' => $calculatorProbe->invocations(),
                'nestingLevelsAtExecute' => $calculatorProbe->nestingLevels(),
            ]
        );
    }
}
