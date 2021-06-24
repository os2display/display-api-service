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
        $page = (int) isset($context['filters']) ? $context['filters']['page'] : 1;
        $itemsPerPage = (int) isset($context['filters']) ? $context['filters']['itemsPerPage'] : 10; // @TODO: figure out to get this from config if not sent in request.
        $current = ($page-1)*$itemsPerPage;

        $results = (new SlideFixtures())->getSlides();

        // Hack:
        /** @var Slide $result */
        foreach ($results as $result) {
            $result->setContent([json_encode($result->getContent())]);
        }

        $start = ($page-1)*$itemsPerPage;
        return new ArrayPaginator($results, 0, $itemsPerPage);
    }
}
