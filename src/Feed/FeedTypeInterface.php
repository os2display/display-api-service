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
     * @param FeedSource $feedSource the feed source
     *
     * @return array|null array of admin options
     */
    public function getAdminFormOptions(FeedSource $feedSource): ?array;

    /**
     * Get feed data for the given feed.
     *
     * @param Feed $feed the feed
     *
     * @return array|null array of data or null
     */
    public function getData(Feed $feed): ?array;

    /**
     * Get config options for $name from $feedSource.
     *
     * @param FeedSource $feedSource
     * @param string $name
     *
     * @return array|null
     */
    public function getConfigOptions(FeedSource $feedSource, string $name): ?array;
}
