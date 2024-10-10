<?php

namespace App\Model;

class CalendarEvent
{
    public string $id;
    public string $title;
    public int $startTimeTimestamp;
    public int $endTimeTimestamp;
    public string $resourceId;
    public string $resourceDisplayName;

    public function __construct(string $id, string $title, int $startTimeTimestamp, int $endTimeTimestamp, string $resourceId, string $resourceDisplayName)
    {
        $this->id = $id;
        $this->title = $title;
        $this->startTimeTimestamp = $startTimeTimestamp;
        $this->endTimeTimestamp = $endTimeTimestamp;
        $this->resourceId = $resourceId;
        $this->resourceDisplayName = $resourceDisplayName;
    }
}
