<?php

namespace App\Model;

class CalendarLocation
{
    public string $id;
    public string $displayName;

    public function __construct(string $id, string $displayName)
    {
        $this->id = $id;
        $this->displayName = $displayName;
    }
}
