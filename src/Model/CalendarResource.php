<?php

namespace App\Model;

class CalendarResource
{
    public string $id;
    public string $locationId;
    public string $displayName;
    public bool $allowInstantBooking;

    public function __construct(string $id, string $locationId, string $displayName, bool $allowInstantBooking)
    {
        $this->id = $id;
        $this->locationId = $locationId;
        $this->displayName = $displayName;
        $this->allowInstantBooking = $allowInstantBooking;
    }
}
