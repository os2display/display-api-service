<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Paginator;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\PaginatorInterface;
use App\Dto\Slide as SlideDTO;
use App\Entity\Tenant\FeedSource;
use App\Entity\Tenant\Slide;
use App\Repository\FeedSourceRepository;
use App\Utils\ValidationUtils;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Uid\Ulid;

/**
 * A Playlist slide state provider.
 *
 * @see https://api-platform.com/docs/v2.7/core/state-providers/
 *
 * @template T of Slide
 */
final class FeedSourceSlideProvider extends AbstractProvider
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly FeedSourceRepository $feedSourceRepository,
        private readonly ValidationUtils $validationUtils,
        private readonly iterable $collectionExtensions,
    ) {}

    protected function provideCollection(Operation $operation, array $uriVariables = [], array $context = []): PaginatorInterface
    {
        $resourceClass = FeedSource::class;
        $id = $uriVariables['id'] ?? '';
        $queryNameGenerator = new QueryNameGenerator();
        $feedSourceUlid = $this->validationUtils->validateUlid($id);

        $queryBuilder = $this->feedSourceRepository->getFeedSourceSlideRelationsFromFeedSourceId($feedSourceUlid);

        foreach ($this->collectionExtensions as $extension) {
            if ($extension instanceof QueryCollectionExtensionInterface) {
                $extension->applyToCollection($queryBuilder, $queryNameGenerator, $resourceClass, $operation,
                    $context);
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

    public function toOutput(object $object): SlideDTO
    {
        assert($object instanceof Slide);
        $output = new SlideDTO();

        $id = $object->getId();
        if (!$id instanceof Ulid) {
            throw new \RuntimeException('Can\'t assign id as Slide->getId() did not return a Ulid object.');
        }

        $output->id = $id;
        $output->title = $object->getTitle();

        return $output;
    }
}
