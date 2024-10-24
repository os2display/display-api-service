<?php

declare(strict_types=1);

namespace App\Utils;

final class PathUtils
{
    public function __construct(
        private array $apiPlatformDefaults,
    ) {}

    /**
     * Get API-platforms configured path prefix with fallback to '/'.
     *
     * @return string
     *   The path prefix
     */
    public function getApiPlatformPathPrefix(): string
    {
        $prefix = $this->apiPlatformDefaults['route_prefix'] ?? '';

        // Make sure that non-empty prefix starts and ends with a slash.
        return empty($prefix) ? '/' : '/'.trim((string) $prefix, '/').'/';
    }
}
