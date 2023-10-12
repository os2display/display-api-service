<?php

namespace App\State;

use ApiPlatform\Api\IriConverterInterface;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\PaginatorInterface;
use ApiPlatform\State\Pagination\TraversablePaginator;
use ApiPlatform\State\ProviderInterface;
use App\Dto\Screen as ScreenDTO;
use App\Entity\Tenant\Slide;

class ScreenProvider implements ProviderInterface
{
    public function __construct(
        private IriConverterInterface $iriConverter,
        private readonly ProviderInterface $collectionProvider
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
            if ($slide = $this->slideRepository->find($uriVariables['id'])) {
                return $this->toDto($slide);
            }
        }

        return null;
    }

    private function toDto(Slide $slide): ScreenDTO
    {
        $output = new ScreenDTO();
        $output->title = $object->getTitle();
        $output->description = $object->getDescription();
        $output->created = $object->getCreatedAt();
        $output->modified = $object->getModifiedAt();
        $output->createdBy = $object->getCreatedBy();
        $output->modifiedBy = $object->getModifiedBy();
        $output->size = (string) $object->getSize();
        $output->orientation = $object->getOrientation();
        $output->resolution = $object->getResolution();

        $output->enableColorSchemeChange = $object->getEnableColorSchemeChange();

        $layout = $object->getScreenLayout();
        $output->layout = $this->iriConverter->getIriFromResource($layout);

        $output->location = $object->getLocation();

        $iri = $this->iriConverter->getIriFromResource($object);
        $output->campaigns = $iri.'/campaigns';

        $objectIri = $this->iriConverter->getIriFromResource($object);
        foreach ($layout->getRegions() as $region) {
            $output->regions[] = $objectIri.'/regions/'.$region->getId().'/playlists';
        }
        $output->inScreenGroups = $objectIri.'/screen-groups';

        $objectUser = $object->getScreenUser();

        if (null != $objectUser) {
            $objectUserId = $objectUser->getId();
            if (null != $objectUserId) {
                $output->screenUser = $objectUserId->jsonSerialize();
            }
        }

        return $output;
    }
}
