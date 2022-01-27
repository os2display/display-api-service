<?php

namespace App\Api;

use ApiPlatform\Core\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\Core\OpenApi\Model;
use ApiPlatform\Core\OpenApi\OpenApi;
use App\Utils\PathUtils;

class OpenApiFactory implements OpenApiFactoryInterface
{
    public function __construct(
        private OpenApiFactoryInterface $decorated,
        private PathUtils $utils
    ) {
    }

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = $this->decorated->__invoke($context);

        $paths = $openApi->getPaths()->getPaths();

        // Remove sub-resource with these paths.
        $exclude = [
            'layouts/regions/{id}',
            'layouts/regions',
            'playlist-screen-regions',
            'playlist-slides/{id}',
            'screen-playlists/{id}',
            'playlist-screen-regions/{id}',
        ];

        $filteredPaths = new Model\Paths();
        foreach ($paths as $path => $pathItem) {
            if (in_array(str_replace($this->utils->getApiPlatformPathPrefix(), '', $path), $exclude)) {
                continue;
            }
            $filteredPaths->addPath($path, $pathItem);
        }

        return $openApi->withPaths($filteredPaths);
    }
}
