<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Put;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\FeedSourceInput;
use App\Entity\Tenant\FeedSource;
use App\Repository\FeedSourceRepository;
use Doctrine\ORM\EntityManagerInterface;
use JsonSchema\Constraints\Factory;
use JsonSchema\SchemaStorage;
use JsonSchema\Validator;
use PHPStan\BetterReflection\Reflection\Exception\ClassDoesNotExist;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class FeedSourceProcessor extends AbstractProcessor
{
    private const string PATTERN_WITHOUT_PROTOCOL = '^((?!-)[A-Za-z0-9-]{1,63}(?<!-)\\.)+[A-Za-z]{2,6}$';
    private const string PATTERN_WITH_PROTOCOL = 'https?:\/\/(www\.)?[-a-zA-Z0-9@:%._\+~#=]{1,256}\.[a-zA-Z0-9()]{1,6}\b([-a-zA-Z0-9()@:%_\+.~#?&//=]*)';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        ProcessorInterface $persistProcessor,
        private readonly ProcessorInterface $removeProcessor,
        private readonly FeedSourceRepository $feedSourceRepository,
    ) {
        parent::__construct($entityManager, $persistProcessor, $removeProcessor);
    }


    public function process($data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        if ($operation instanceof DeleteOperationInterface) {
            $queryBuilder = $this->feedSourceRepository->getFeedSourceSlideRelationsFromFeedSourceId($uriVariables['id']);
            $hasSlides = $queryBuilder->getQuery()->getResult();
            if ($hasSlides) {
                throw new ConflictHttpException('This feed source is used by one or more slides and cannot be deleted.');
            }
            $this->removeProcessor->process($data, $operation, $uriVariables, $context);
        }
        parent::process($data, $operation, $uriVariables, $context);
    }

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

        $this->validateFeedSource($object, $operation);

        return $feedSource;
    }

    /**
     * @throws \JsonException
     */
    private function validateFeedSource(object $object, Operation $operation): void
    {
        $schemaStorage = new SchemaStorage();
        $feedSourceValidationSchema = (new FeedSource())->getSchema();
        $schemaStorage->addSchema('file://contentSchema', $feedSourceValidationSchema);
        $validator = new Validator(new Factory($schemaStorage));
        $validator->validate($object, $feedSourceValidationSchema);

        if (!$validator->isValid()) {
            throw new InvalidArgumentException($validator->getErrors()[0]['property'].' '.$validator->getErrors()[0]['message']);
        }

        $feedTypeClassName = $object->feedType;

        if (!class_exists($feedTypeClassName)) {
            throw new ClassDoesNotExist('Provided feed type class does not exist');
        }
        $feedTypeValidationSchema = $feedTypeClassName::getSchema();

        if($operation instanceof Put && empty($object->secrets)) {
            return;
        }
        $secrets = (object) $object->secrets;
        $validator->validate($secrets, $feedTypeValidationSchema);

        if (!$validator->isValid()) {
            throw new InvalidArgumentException($validator->getErrors()[0]['property'].' '.$validator->getErrors()[0]['message']);
        }
    }
}
