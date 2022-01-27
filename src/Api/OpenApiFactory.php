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

        // Add auth endpoint
        $schemas = $openApi->getComponents()->getSchemas();
        $schemas['Token'] = new \ArrayObject([
            'type' => 'object',
            'properties' => [
                'token' => [
                    'type' => 'string',
                    'readOnly' => true,
                ],
            ],
        ]);
        $schemas['Credentials'] = new \ArrayObject([
            'type' => 'object',
            'properties' => [
                'email' => [
                    'type' => 'string',
                    'example' => 'johndoe@example.com',
                ],
                'password' => [
                    'type' => 'string',
                    'example' => 'apassword',
                ],
            ],
        ]);

        $pathItem = new Model\PathItem(
            ref: 'JWT Token',
            post: new Model\Operation(
                operationId: 'postCredentialsItem',
                tags: ['Auth Token'],
                responses: [
                    '200' => [
                        'description' => 'Get JWT token',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/Token',
                                ],
                            ],
                        ],
                    ],
                ],
                summary: 'Get JWT token to login.',
                requestBody: new Model\RequestBody(
                    description: 'Generate new JWT Token',
                    content: new \ArrayObject([
                        'application/json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/Credentials',
                            ],
                        ],
                    ]),
                ),
            ),
        );
        $openApi->getPaths()->addPath('/v1/authentication_token', $pathItem);

        $schemas['ScreenLoginOutput'] = new \ArrayObject([
            'type' => 'object',
            'properties' => [
                'bindKey' => [
                    'type' => 'string',
                    'readOnly' => true,
                ],
                'token' => [
                    'type' => 'string',
                    'readOnly' => true,
                ],
                'screenId' => [
                    'type' => 'string',
                    'readOnly' => true,
                ]
            ]
        ]);

        $schemas['ScreenLoginInput'] = new \ArrayObject([
            'type' => 'object',
            'uniqueLoginId' => [
                'type' => 'string',
                'required' => true,
            ],
        ]);

        $screenPathItem = new Model\PathItem(
            ref: 'JWT Token',
            post: new Model\Operation(
                operationId: 'postLoginInfoScreen',
                tags: ['Auth Token'],
                responses: [
                    '200' => [
                        'description' => 'Login with bindKey to get JWT token for screen',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/ScreenLoginOutput',
                                ],
                            ],
                        ],
                    ],
                ],
                summary: 'Get login info for a screen.',
                requestBody: new Model\RequestBody(
                    description: 'Get login info with JWT token for given nonce',
                    content: new \ArrayObject([
                        'application/json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/ScreenLoginInput',
                            ],
                        ],
                    ]),
                ),
            ),
        );
        $openApi->getPaths()->addPath('/v1/authentication/screen', $screenPathItem);

        $schemas['ScreenBindObject'] = new \ArrayObject([
            'type' => 'object',
            'properties' => [
                'bindKey' => [
                    'type' => 'string',
                ],
            ]
        ]);

        $screenBindItem = new Model\PathItem(
            ref: 'JWT Token',
            post: new Model\Operation(
                operationId: 'postScreenBindKey',
                tags: ['Screens'],
                responses: [
                    '201' => [
                        'description' => 'Bind screen with bind key',
                    ],
                ],
                summary: 'Bind screen with BindKey',
                parameters: [
                    new Model\Parameter(
                        name: 'id',
                        in: 'path'
                    )
                ],
                requestBody: new Model\RequestBody(
                    description: 'Get login info with JWT token for given nonce',
                    content: new \ArrayObject([
                        'application/json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/ScreenBindObject',
                            ],
                        ],
                    ]),
                ),
            ),
        );
        $openApi->getPaths()->addPath('/v1/screens/{id}/bind', $screenBindItem);

        // Remove sub-resource with these paths.
        $exclude = [
            'layouts/regions/{id}',
            'layouts/regions',
            'playlist-screen-regions',
            'playlist-slides/{id}',
            'playlist-screen-regions/{id}',
        ];

        $paths = $openApi->getPaths()->getPaths();

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
