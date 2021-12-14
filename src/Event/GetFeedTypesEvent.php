<?php

namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;

class GetFeedTypesEvent extends Event
{
    public const NAME = 'app.feed.get_feeds';

    private $feedTypes = [];

    public function addFeedType($className)
    {
        $this->feedTypes[] = $className;
    }

    public function getFeedTypes()
    {
        return $this->feedTypes;
    }
}
