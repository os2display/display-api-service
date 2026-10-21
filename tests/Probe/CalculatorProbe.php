<?php

declare(strict_types=1);

namespace App\Tests\Probe;

use App\Service\RelationsChecksumCalculator;
use Doctrine\DBAL\Connection;

/**
 * Test-only decorator around RelationsChecksumCalculator that counts execute() calls.
 *
 * Registered under "when@test" in config/services.yaml.
 */
class CalculatorProbe extends RelationsChecksumCalculator
{
    private int $invocations = 0;

    public function __construct(
        private readonly RelationsChecksumCalculator $inner,
        Connection $connection,
    ) {
        parent::__construct($connection);
    }

    public function execute(bool $withWhereClause = true): void
    {
        ++$this->invocations;

        $this->inner->execute($withWhereClause);
    }

    public function reset(): void
    {
        $this->invocations = 0;
    }

    public function invocations(): int
    {
        return $this->invocations;
    }
}
