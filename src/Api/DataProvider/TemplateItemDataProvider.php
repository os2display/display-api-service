<?php

namespace App\Api\DataProvider;

use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use App\Api\Fixtures\MediaFixtures;
use App\Api\Fixtures\TemplateFixtures;
use App\Api\Model\Template;

/**
 * Class TemplateItemDataProvider
 */
final class TemplateItemDataProvider implements ItemDataProviderInterface, RestrictedDataProviderInterface
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
    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = []): ?Template
    {
        return (new TemplateFixtures())->getTemplate($id);
    }
}
