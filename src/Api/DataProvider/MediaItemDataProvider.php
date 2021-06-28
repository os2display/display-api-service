<?php

namespace App\Api\DataProvider;

use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use App\Api\Fixtures\MediaFixtures;
use App\Api\Model\Media;

/**
 * Class MediaItemDataProvider.
 */
final class MediaItemDataProvider implements ItemDataProviderInterface, RestrictedDataProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return Media::class === $resourceClass;
    }

    /**
     * {@inheritdoc}
     */
    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = []): ?Media
    {
        return (new MediaFixtures())->getMedia($id);
    }
}
