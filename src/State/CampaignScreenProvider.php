<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Paginator;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\PaginatorInterface;
use ApiPlatform\State\ProviderInterface;
use App\Dto\ScreenCampaign as ScreenCampaignDTO;
use App\Entity\Tenant\Screen;
use App\Entity\Tenant\ScreenGroupCampaign;
use App\Repository\ScreenCampaignRepository;
use App\Utils\ValidationUtils;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * A campaign screen state provider.
 *
 * @see https://api-platform.com/docs/v2.7/core/state-providers/
 *
 * @template T of Screen
 */
final class CampaignScreenProvider extends AbstractProvider
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly ScreenCampaignRepository $screenCampaignRepository,
        private readonly ValidationUtils $validationUtils,
        private readonly iterable $collectionExtensions,
        ProviderInterface $collectionProvider,
        private readonly ScreenCampaignProvider $screenCampaignProvider
    ) {
        parent::__construct($collectionProvider, $this->screenCampaignRepository);
    }

    public function provideCollection(Operation $operation, array $uriVariables = [], array $context = []): PaginatorInterface
    {
        $resourceClass = ScreenGroupCampaign::class;
        $id = $uriVariables['id'] ?? '';
        $queryNameGenerator = new QueryNameGenerator();
        $campaignId = $this->validationUtils->validateUlid($id);

        // Get playlist to check shared-with-tenants
        $queryBuilder = $this->screenCampaignRepository->getScreensBasedOnCampaign($campaignId);

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

    public function toOutput(object $object): ScreenCampaignDTO
    {
        return $this->screenCampaignProvider->toOutput($object);
    }
}
