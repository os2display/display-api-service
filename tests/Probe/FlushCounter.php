<?php

declare(strict_types=1);

namespace App\Tests\Probe;

/**
 * Test-only Doctrine postFlush listener counting flushes.
 *
 * Registered in config/services_test.yaml.
 */
class FlushCounter
{
    private int $count = 0;

    public function postFlush(): void
    {
        ++$this->count;
    }

    public function reset(): void
    {
        $this->count = 0;
    }

    public function count(): int
    {
        return $this->count;
    }
}
