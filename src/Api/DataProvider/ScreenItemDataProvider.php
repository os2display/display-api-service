<?php

namespace App\Api\DataProvider;

use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use App\Api\Fixtures\ScreenFixtures;
use App\Api\Model\Screen;


/**
 * Class ScreenItemDataProvider
 */
final class ScreenItemDataProvider implements ItemDataProviderInterface, RestrictedDataProviderInterface
{
    /**
     * @{inheritdoc}
     */
    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return Screen::class === $resourceClass;
    }

    /**
     * @{inheritdoc}
     */
    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = []): ?Screen
    {
        return (new ScreenFixtures())->getScreen($id);
    }
}
