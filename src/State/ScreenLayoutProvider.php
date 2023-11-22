<?php

namespace App\State;

use ApiPlatform\State\ProviderInterface;
use App\Dto\ScreenLayout as ScreenLayoutDTO;
use App\Entity\ScreenLayout;
use App\Repository\ScreenLayoutRepository;

class ScreenLayoutProvider extends AbstractProvider
{
    public function __construct(
        ProviderInterface $collectionProvider,
        ScreenLayoutRepository $entityRepository,
    ) {
        parent::__construct($collectionProvider, $entityRepository);
    }

    public function toOutput(object $object): object
    {
        /** @var ScreenLayout $object */
        $output = new ScreenLayoutDTO();
        $output->id = $object->getId();
        $output->title = $object->getTitle();
        $output->grid['rows'] = $object->getGridRows();
        $output->grid['columns'] = $object->getGridColumns();
        $output->regions = $object->getRegions();

        return $output;
    }
}
