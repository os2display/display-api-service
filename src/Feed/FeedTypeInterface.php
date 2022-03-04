<?php

namespace App\Feed;

use App\Entity\Tenant\Feed;
use App\Entity\Tenant\FeedSource;
use Symfony\Component\HttpFoundation\Request;

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
     * @return array|\stdClass|null array or stdClass of data or null
     */
    public function getData(Feed $feed): array|\stdClass|null;

    /**
     * Get config options for $name from $feedSource.
     *
     * @param Request $request
     * @param FeedSource $feedSource
     * @param string $name
     *
     * @return array|null
     */
    public function getConfigOptions(Request $request, FeedSource $feedSource, string $name): array|\stdClass|null;

    /**
     * Get list of required secrets.
     *
     * @return array
     */
    public function getRequiredSecrets(): array;

    /**
     * Get list of required configuration.
     *
     * @return array
     */
    public function getRequiredConfiguration(): array;
}
