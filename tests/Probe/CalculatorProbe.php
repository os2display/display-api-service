<?php

declare(strict_types=1);

namespace App\Tests\Probe;

use App\Service\RelationsChecksumCalculator;
use Doctrine\DBAL\Connection;

/**
 * Test-only decorator around RelationsChecksumCalculator.
 *
 * Records each execute() call and the connection's transaction nesting level at that moment.
 * Registered in config/services_test.yaml.
 */
class CalculatorProbe extends RelationsChecksumCalculator
{
    /** @var int[] */
    private array $nestingLevels = [];

    public function __construct(
        private readonly RelationsChecksumCalculator $inner,
        private readonly Connection $connection,
    ) {
        parent::__construct($connection);
    }

    public function execute(bool $withWhereClause = true): void
    {
        $this->nestingLevels[] = $this->connection->getTransactionNestingLevel();

        $this->inner->execute($withWhereClause);
    }

    public function reset(): void
    {
        $this->nestingLevels = [];
    }

    public function invocations(): int
    {
        return count($this->nestingLevels);
    }

    /**
     * @return int[] transaction nesting level at each execute() call, in call order
     */
    public function nestingLevels(): array
    {
        return $this->nestingLevels;
    }
}
