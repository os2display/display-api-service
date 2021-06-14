<?php

namespace App\Api\DataProvider;

use ApiPlatform\Core\DataProvider\ArrayPaginator;
use ApiPlatform\Core\DataProvider\ContextAwareCollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use App\Api\Fixtures\TemplateFixtures;
use App\Api\Model\Template;

/**
 * Class TemplateCollectionDataProvider
 */
final class TemplateCollectionDataProvider implements ContextAwareCollectionDataProviderInterface, RestrictedDataProviderInterface
{
    /**
     * @{inheritdoc}
     */
    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return Template::class === $resourceClass;
    }

    /**
     * @{inheritdoc}
     */
    public function getCollection(string $resourceClass, string $operationName = null, array $context = []): iterable
    {
        $page = (int) $context['filters']['page'];
        $itemsPerPage = (int) $context['filters']['itemsPerPage']; // @TODO: figure out to get this from config if not sent in request.
        $current = ($page-1)*$itemsPerPage;

        $results =[
            (new TemplateFixtures())->getTemplate('457d6ecb-6378-4299-bfcb-53cbaaaa6f65'),
        ];

        $start = ($page-1)*$itemsPerPage;
        return new ArrayPaginator($results, 0, $itemsPerPage);
    }
}
