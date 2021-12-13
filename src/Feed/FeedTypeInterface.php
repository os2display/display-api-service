<?php

namespace App\Feed;

interface FeedTypeInterface
{
    public function getFeedType(): ?string;
}
