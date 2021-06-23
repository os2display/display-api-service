<?php

namespace App\Api\DataProvider;

use ApiPlatform\Core\DataProvider\ArrayPaginator;
use ApiPlatform\Core\DataProvider\ContextAwareCollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use App\Api\Fixtures\SlideFixtures;
use App\Api\Model\Slide;

/**
 * Class SlideCollectionDataProvider
 */
final class SlideCollectionDataProvider implements ContextAwareCollectionDataProviderInterface, RestrictedDataProviderInterface
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
    public function getCollection(string $resourceClass, string $operationName = null, array $context = []): iterable
    {
        $page = (int) $context['filters']['page'];
        $itemsPerPage = (int) $context['filters']['itemsPerPage']; // @TODO: figure out to get this from config if not sent in request.
        $current = ($page-1)*$itemsPerPage;

        $results = (new SlideFixtures())->getSlides();

        $start = ($page-1)*$itemsPerPage;
        return new ArrayPaginator($results, 0, $itemsPerPage);
    }
}
