<?php

namespace App\Feed;

use App\Entity\Feed;
use App\Entity\FeedSource;

/**
 * Interface that feed types must implement.
 */
interface FeedTypeInterface
{
    /**
     * Get admin form options that will be exposed in the admin.
     *
     * @return array|null Array of admin options.
     */
    public function getAdminFormOptions(): ?array;

    /**
     * Get feed data for the given feed.
     *
     * @param FeedSource $feedSource The feed source.
     * @param Feed $feed The feed.
     * @return array|null Array of data or null.
     */
    public function getData(FeedSource $feedSource, Feed $feed): ?array;
}
