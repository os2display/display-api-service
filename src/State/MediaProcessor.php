<?php

namespace App\State;

use ApiPlatform\State\ProcessorInterface;
use Doctrine\ORM\EntityManagerInterface;

class MediaProcessor extends AbstractProcessor
{
    public function __construct(
        EntityManagerInterface $entityManager,
        ProcessorInterface $persistProcessor,
        ProcessorInterface $removeProcessor,
        MediaProvider $provider
    ) {
        parent::__construct($entityManager, $persistProcessor, $removeProcessor, $provider);
    }
}
