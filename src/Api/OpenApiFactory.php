<?php

namespace App\Api;

use ApiPlatform\Core\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\Core\OpenApi\Model;
use ApiPlatform\Core\OpenApi\OpenApi;

class OpenApiFactory implements OpenApiFactoryInterface
{
    public function __construct(
        private OpenApiFactoryInterface $decorated
    ) {
    }

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = $this->decorated->__invoke($context);

        $paths = $openApi->getPaths()->getPaths();

        // @TODO: Get prefix from configuration.
        // Remove sub-resource with these paths.
        $exclude = [
            '/v1/layouts/regions/{id}',
            '/v1/layouts/regions',
            '/v1/playlist-screen-regions',
            '/v1/playlist-slides/{id}',
        ];

        $filteredPaths = new Model\Paths();
        foreach ($paths as $path => $pathItem) {
            if (in_array($path, $exclude)) {
                continue;
            }
            $filteredPaths->addPath($path, $pathItem);
        }

        return $openApi->withPaths($filteredPaths);
    }
}
