<?php

namespace App\State;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\PaginatorInterface;
use ApiPlatform\State\Pagination\TraversablePaginator;
use ApiPlatform\State\ProviderInterface;
use App\Dto\Template as TemplateDTO;
use App\Entity\Template;
use App\Repository\TemplateRepository;

class TemplateProvider implements ProviderInterface
{
    public function __construct(
        // @see https://api-platform.com/docs/core/state-providers/#hooking-into-the-built-in-state-provider
        private readonly ProviderInterface $collectionProvider,
        private readonly TemplateRepository $templateRepository
    ) {}

    /**
     * {@inheritdoc}
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if ($operation instanceof GetCollection) {
            $collection = $this->collectionProvider->provide($operation, $uriVariables, $context);
            if ($collection instanceof PaginatorInterface) {
                // @see https://api-platform.com/docs/core/pagination/#pagination-for-custom-state-providers
                return new TraversablePaginator(
                    new \ArrayIterator(
                        array_map($this->toDto(...), iterator_to_array($collection))
                    ),
                    $collection->getCurrentPage(),
                    $collection->getItemsPerPage(),
                    $collection->getTotalItems()
                );
            }
        } elseif ($operation instanceof Get) {
            if ($slide = $this->templateRepository->find($uriVariables['id'])) {
                return $this->toDto($slide);
            }
        }

        return null;
    }

    private function toDto(Template $template): TemplateDTO
    {
        $output = new TemplateDTO();
        $output->id = $template->getId();
        $output->title = $template->getTitle();
        $output->description = $template->getDescription();
        $output->modified = $template->getModifiedAt();
        $output->created = $template->getCreatedAt();
        $output->modifiedBy = $template->getModifiedBy();
        $output->createdBy = $template->getCreatedBy();
        $output->resources = $template->getResources();

        return $output;
    }
}
