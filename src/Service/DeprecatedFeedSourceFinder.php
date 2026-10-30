<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Tenant\FeedSource;
use App\Repository\FeedSourceRepository;

/**
 * Locates FeedSource rows that reference a feed type deprecated in 2.x and
 * removed in 3.0.0.
 *
 * The feed type is stored in the `feed_source.feed_type` column as the
 * fully-qualified class name string (not a foreign key). Once a feed type class
 * is removed those strings can no longer be resolved, so the rows must be cleaned
 * up before upgrading. This finder identifies them by the known list of removed
 * class names below — kept as string literals (not `::class`) so detection keeps
 * working after the classes are deleted.
 */
class DeprecatedFeedSourceFinder
{
    /**
     * Feed types deprecated in 2.x and removed in 3.0.0.
     *
     * @var list<string>
     */
    public const array DEPRECATED_FEED_TYPES = [
        'App\\Feed\\SparkleIOFeedType',
        'App\\Feed\\EventDatabaseApiFeedType',
        'App\\Feed\\KobaFeedType',
    ];

    public function __construct(
        private readonly FeedSourceRepository $feedSourceRepository,
    ) {}

    /**
     * Find all feed sources referencing a deprecated feed type, across all tenants.
     *
     * Uses the repository directly (not the API Platform state layer), so the
     * tenant scoping applied via App\Filter\TenantExtension does not restrict the
     * result — every tenant's rows are returned.
     *
     * @return FeedSource[]
     */
    public function findDeprecated(): array
    {
        return $this->feedSourceRepository->findBy(['feedType' => self::DEPRECATED_FEED_TYPES]);
    }

    public function countDeprecated(): int
    {
        return count($this->findDeprecated());
    }
}
