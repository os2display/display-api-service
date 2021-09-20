<?php

namespace App\Api;

use ApiPlatform\Core\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\Core\OpenApi\Model;
use ApiPlatform\Core\OpenApi\OpenApi;

class OpenApiFactory implements OpenApiFactoryInterface
{
    private OpenApiFactoryInterface $decorated;

    public function __construct(OpenApiFactoryInterface $decorated)
    {
        $this->decorated = $decorated;
    }

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = $this->decorated->__invoke($context);

        $paths = $openApi->getPaths()->getPaths();

        // Remove sub-resource with these paths.
        $exclude = [
            '/v1/screen-layout-regions/{ulid}',
            '/v1/screen-layout-regions',
            '/v1/playlist-screen-regions',
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
