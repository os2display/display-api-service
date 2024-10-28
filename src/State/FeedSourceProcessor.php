<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\FeedSourceInput;
use App\Entity\Tenant\FeedSource;
use Doctrine\ORM\EntityManagerInterface;

class FeedSourceProcessor extends AbstractProcessor
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        ProcessorInterface $persistProcessor,
        ProcessorInterface $removeProcessor,
    ) {
        parent::__construct($entityManager, $persistProcessor, $removeProcessor);
    }

    /**
     * {@inheritdoc}
     */
    public function process(mixed $object, Operation $operation, array $uriVariables = [], array $context = [])
    {
        $entity = $this->fromInput($object, $operation, $uriVariables, $context);
        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        return $entity;
        }

    /**
     * @return T
     */
    protected function fromInput(mixed $object, Operation $operation, array $uriVariables, array $context): FeedSource
    {
        // FIXME Do we really have to do (something like) this to load an existing object into the entity manager?
        $feedSource = $this->loadPrevious(new FeedSource(), $context);

        /* @var FeedSourceInput $object */
        empty($object->title) ?: $feedSource->setTitle($object->title);
        empty($object->description) ?: $feedSource->setDescription($object->description);
        empty($object->createdBy) ?: $feedSource->setCreatedBy($object->createdBy);
        empty($object->modifiedBy) ?: $feedSource->setModifiedBy($object->modifiedBy);
        empty($object->secrets) ?: $feedSource->setSecrets($object->secrets);
        empty($object->feedType) ?: $feedSource->setFeedType($object->feedType);
        empty($object->supportedFeedOutputType) ?: $feedSource->setSupportedFeedOutputType($object->supportedFeedOutputType);

        return $feedSource;
    }
}
