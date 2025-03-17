<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Paginator;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\PaginatorInterface;
use ApiPlatform\State\ProviderInterface;
use App\Dto\ScreenGroupCampaign as ScreenGroupCampaignDTO;
use App\Entity\Tenant\ScreenGroupCampaign;
use App\Repository\ScreenGroupCampaignRepository;
use App\Utils\ValidationUtils;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;
use Symfony\Component\HttpFoundation\RequestStack;

class CampaignScreenGroupProvider extends AbstractProvider
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly ScreenGroupCampaignRepository $screenGroupCampaignRepository,
        private readonly ValidationUtils $validationUtils,
        private readonly iterable $collectionExtensions,
        ProviderInterface $collectionProvider,
        private readonly ScreenGroupCampaignProvider $screenGroupCampaignProvider,
    ) {
        parent::__construct($collectionProvider, $this->screenGroupCampaignRepository);
    }

    protected function provideCollection(Operation $operation, array $uriVariables = [], array $context = []): PaginatorInterface
    {
        $resourceClass = ScreenGroupCampaign::class;
        $id = $uriVariables['id'] ?? '';
        $queryNameGenerator = new QueryNameGenerator();
        $campaignUlid = $this->validationUtils->validateUlid($id);

        $queryBuilder = $this->screenGroupCampaignRepository->getScreenGroupsFromCampaignId($campaignUlid);

        // Filter the query-builder with tenant extension.
        foreach ($this->collectionExtensions as $extension) {
            if ($extension instanceof QueryCollectionExtensionInterface) {
                $extension->applyToCollection($queryBuilder, $queryNameGenerator, $resourceClass, $operation, $context);
            }
        }

        $request = $this->requestStack->getCurrentRequest();
        $itemsPerPage = $request->query?->get('itemsPerPage') ?? 10;
        $page = $request->query?->get('page') ?? 1;
        $firstResult = ((int) $page - 1) * (int) $itemsPerPage;
        $query = $queryBuilder->getQuery()
            ->setFirstResult($firstResult)
            ->setMaxResults((int) $itemsPerPage);

        $doctrinePaginator = new DoctrinePaginator($query);

        return new Paginator($doctrinePaginator);
    }

    public function toOutput(object $object): ScreenGroupCampaignDTO
    {
        return $this->screenGroupCampaignProvider->toOutput($object);
    }
}
