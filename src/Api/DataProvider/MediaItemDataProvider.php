<?php
// api/src/DataProvider/BlogPostCollectionDataProvider.php

namespace App\Api\DataProvider;

use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use App\Api\Model\Media;

final class MediaItemDataProvider implements ItemDataProviderInterface, RestrictedDataProviderInterface
{
    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return Media::class === $resourceClass;
    }

    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = []): ?Media
    {
        // Retrieve the blog post item from somewhere then return it or null if not found
        $media = new Media();
        $media->id = '497f6eca-4576-4883-cfeb-53cbffba6f08';
        $media->title = 'test';
        $media->addAsset('image', 'http://test.dk/test.png');

        return $media;
    }
}
