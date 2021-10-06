<?php

namespace App\Utils;

final class PathUtils
{
    public function __construct(
        private array $apiPlatformDefaults
    ) {
    }

    /**
     * Get API-platforms configured path prefix with fallback to '/'.
     *
     * @return string
     *   The path prefix
     */
    public function getApiPlatformPathPrefix(): string
    {
        $prefix = '/';
        $config = $this->apiPlatformDefaults;
        if (!empty($config['attributes']['route_prefix'])) {
            $prefix = $config['attributes']['route_prefix'];
            if (!str_starts_with($prefix, '/')) {
                $prefix = '/'.$prefix;
            }
            if (!str_ends_with($prefix, '/')) {
                $prefix .= '/';
            }
        }

        return $prefix;
    }
}
