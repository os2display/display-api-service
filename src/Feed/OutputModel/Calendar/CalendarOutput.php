<?php

namespace App\Feed\OutputModel\Calendar;

use App\Feed\OutputModel\OutputInterface;

class CalendarOutput implements OutputInterface
{
    public function __construct(
        /** @var Event[] $events */
        public array $events,
    ) {}

    public function toArray(): array
    {
        return $this->events;
    }
}
