<?php

declare(strict_types=1);

namespace App\NemDeling;

use App\NemDeling\Exception\NemDelingSyncInProgressException;

/**
 * In-process sync lock mirroring integration-source concurrency handling.
 */
final class NemDelingSyncLock
{
    private int $eventsAreSyncing = 0;

    private int $eventListsAreSyncing = 0;

    public function acquireEvents(): void
    {
        $this->acquire($this->eventsAreSyncing, 'Events are already being synced.');
    }

    public function releaseEvents(): void
    {
        $this->eventsAreSyncing = 0;
    }

    public function acquireEventLists(): void
    {
        $this->acquire($this->eventListsAreSyncing, 'Event lists are already being synced.');
    }

    public function releaseEventLists(): void
    {
        $this->eventListsAreSyncing = 0;
    }

    private function acquire(int &$counter, string $message): void
    {
        if ($counter > 0 && $counter < 5) {
            ++$counter;
            throw new NemDelingSyncInProgressException($message);
        }

        $counter = 1;
    }
}
