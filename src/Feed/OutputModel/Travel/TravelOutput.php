<?php

declare(strict_types=1);

namespace App\Feed\OutputModel\Travel;

class TravelOutput
{
    public function __construct(
        /** @var list<Station> $stations */
        public array $stations,
    ) {}

    /**
     * @return list<Station>
     */
    public function toArray(): array
    {
        return $this->stations;
    }
}
