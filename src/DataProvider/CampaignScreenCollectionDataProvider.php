<?php

namespace App\DataProvider;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Paginator;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Core\DataProvider\ContextAwareCollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use App\Entity\Tenant\ScreenCampaign;
use App\Repository\ScreenCampaignRepository;
use App\Utils\ValidationUtils;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;
use Symfony\Component\HttpFoundation\RequestStack;

final class CampaignScreenCollectionDataProvider implements ContextAwareCollectionDataProviderInterface, RestrictedDataProviderInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private ScreenCampaignRepository $screenCampaignRepository,
        private ValidationUtils $validationUtils,
        private iterable $collectionExtensions
    ) {}

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return ScreenCampaign::class === $resourceClass && 'getCampaignScreens' === $operationName;
    }

    public function getCollection(string $resourceClass, string $operationName = null, array $context = []): Paginator
    {
        $currentRequest = $this->requestStack->getCurrentRequest();
        $itemsPerPage = $currentRequest->query?->get('itemsPerPage') ?? 10;
        $page = $currentRequest->query?->get('page') ?? 1;
        $id = $currentRequest->attributes?->get('id') ?? '';

        $queryNameGenerator = new QueryNameGenerator();
        $campaignId = $this->validationUtils->validateUlid($id);

        // Get playlist to check shared-with-tenants
        $queryBuilder = $this->screenCampaignRepository->getScreensBasedOnCampaign($campaignId);

        // Filter the query-builder with tenant extension.
        foreach ($this->collectionExtensions as $extension) {
            $extension->applyToCollection($queryBuilder, $queryNameGenerator, $resourceClass, $operationName, $context);
        }

        $firstResult = ((int) $page - 1) * (int) $itemsPerPage;
        $query = $queryBuilder->getQuery()
            ->setFirstResult($firstResult)
            ->setMaxResults((int) $itemsPerPage);

        $doctrinePaginator = new DoctrinePaginator($query);

        return new Paginator($doctrinePaginator);
    }
}
