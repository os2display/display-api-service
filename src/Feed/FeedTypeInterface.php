<?php

namespace App\Feed;

use App\Entity\Feed;
use App\Entity\FeedSource;

interface FeedTypeInterface
{
    public function getAdmin(FeedSource $feedSource): ?array;

    public function getData(Feed $feed): ?array;

    public function getConfigOptions(FeedSource $feedSource, string $name): ?array;
}
