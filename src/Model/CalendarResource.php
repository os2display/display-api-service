<?php

namespace App\Model;

class CalendarResource
{
    public string $id;
    public string $locationId;
    public string $displayName;

    public function __construct(string $id, string $locationId, string $displayName)
    {
        $this->id = $id;
        $this->locationId = $locationId;
        $this->displayName = $displayName;
    }
}
