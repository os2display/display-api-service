<?php

namespace App\DataProvider;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Paginator;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Core\DataProvider\ContextAwareCollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use App\Entity\Tenant\ScreenGroupCampaign;
use App\Repository\ScreenGroupCampaignRepository;
use App\Utils\ValidationUtils;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Security;

final class CampaignScreenGroupCollectionDataProvider implements ContextAwareCollectionDataProviderInterface, RestrictedDataProviderInterface
{
    public function __construct(
        private Security $security,
        private RequestStack $requestStack,
        private ScreenGroupCampaignRepository $screenGroupCampaignRepository,
        private ValidationUtils $validationUtils,
        private iterable $collectionExtensions
    ) {}

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return ScreenGroupCampaign::class === $resourceClass && 'getCampaignScreensGroups' === $operationName;
    }

    public function getCollection(string $resourceClass, string $operationName = null, array $context = []): Paginator
    {
        $itemsPerPage = (int) $this->requestStack->getCurrentRequest()->query->get('itemsPerPage', '10');
        $page = (int) $this->requestStack->getCurrentRequest()->query->get('page', '1');
        $id = $this->requestStack->getCurrentRequest()->attributes->get('id');
        $queryNameGenerator = new QueryNameGenerator();
        $campaignUlid = $this->validationUtils->validateUlid($id);

        $queryBuilder = $this->screenGroupCampaignRepository->getScreenGroupsFromCampaignId($campaignUlid);

        foreach ($this->collectionExtensions as $extension) {
            $extension->applyToCollection($queryBuilder, $queryNameGenerator, $resourceClass, $operationName, $context);
            if ($extension instanceof QueryResultItemExtensionInterface && $extension->supportsResult($resourceClass, $operationName, $context)) {
                return $extension->getResult($queryBuilder, $resourceClass, $operationName, $context);
            }
        }

        $firstResult = ($page - 1) * $itemsPerPage;
        $query = $queryBuilder->getQuery()
            ->setFirstResult($firstResult)
            ->setMaxResults($itemsPerPage);

        $doctrinePaginator = new DoctrinePaginator($query);

        return new Paginator($doctrinePaginator);
    }
}
