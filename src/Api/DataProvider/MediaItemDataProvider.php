<?php
// api/src/DataProvider/BlogPostCollectionDataProvider.php

namespace App\Api\DataProvider;

use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use App\Api\Fixtures\MediaFixtures;
use App\Api\Model\Media;

final class MediaItemDataProvider implements ItemDataProviderInterface, RestrictedDataProviderInterface
{
    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return Media::class === $resourceClass;
    }

    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = []): ?Media
    {
        $fixtures = new MediaFixtures();
        $data = $fixtures->get($id);
        if (!is_null($data)) {
            $media = new Media();
            foreach ($data as $key => $value) {
                switch ($key) {
                    case 'assets':
                        foreach ($value as $asset) {
                            $media->addAsset($asset['type'], $asset['uri']);
                        }
                        break;

                    default:
                        $media->$key = $value;
                }
            }
            return $media;
        }

        return null;
    }
}
