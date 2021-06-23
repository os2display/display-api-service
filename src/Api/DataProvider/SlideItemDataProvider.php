<?php

namespace App\Api\DataProvider;

use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use App\Api\Fixtures\SlideFixtures;
use App\Api\Model\Slide;

/**
 * Class SlideItemDataProvider
 */
final class SlideItemDataProvider implements ItemDataProviderInterface, RestrictedDataProviderInterface
{
    /**
     * @{inheritdoc}
     */
    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return Slide::class === $resourceClass;
    }

    /**
     * @{inheritdoc}
     */
    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = []): ?Slide
    {
        return (new SlideFixtures())->getSlide($id);
    }
}
