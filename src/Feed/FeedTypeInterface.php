<?php

namespace App\Feed;

use App\Entity\Feed;
use App\Entity\FeedSource;

interface FeedTypeInterface
{
    public function getAdmin(): ?array;

    public function getData(FeedSource $feedSource, Feed $feed): ?array;
}
