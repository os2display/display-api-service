<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Put;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Interfaces\TenantScopedUserInterface;
use App\Entity\Tenant\FeedSource;
use App\Exceptions\UnknownFeedTypeException;
use App\Repository\FeedSourceRepository;
use App\Service\FeedService;
use Doctrine\ORM\EntityManagerInterface;
use JsonSchema\Constraints\Factory;
use JsonSchema\SchemaStorage;
use JsonSchema\Validator;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class FeedSourceProcessor extends AbstractProcessor
{
    public function __construct(
        EntityManagerInterface $entityManager,
        ProcessorInterface $persistProcessor,
        ProcessorInterface $removeProcessor,
        private readonly FeedSourceRepository $feedSourceRepository,
        private readonly FeedService $feedService,
        private readonly Security $security,
    ) {
        parent::__construct($entityManager, $persistProcessor, $removeProcessor);
    }

    public function process($data, Operation $operation, array $uriVariables = [], array $context = []): ?object
    {
        if ($operation instanceof DeleteOperationInterface) {
            $queryBuilder = $this->feedSourceRepository->getFeedSourceSlideRelationsFromFeedSourceId($uriVariables['id']);
            $hasSlides = $queryBuilder->getQuery()->getResult();
            if ($hasSlides) {
                throw new ConflictHttpException('This feed source is used by one or more slides and cannot be deleted.');
            }
        }

        return parent::process($data, $operation, $uriVariables, $context);
    }

    /**
     * @throws UnknownFeedTypeException
     */
    protected function fromInput(mixed $object, Operation $operation, array $uriVariables, array $context): FeedSource
    {
        // Set feed source properties
        $feedSource = $this->loadPrevious(new FeedSource(), $context);

        if (!$feedSource instanceof FeedSource) {
            throw new InvalidArgumentException('object must by of type FeedSource');
        }

        // Set secrets as result of previous values and secrets array.
        $previousSecrets = $feedSource->getSecrets() ?? [];
        $newSecrets = $object->secrets ?? [];
        $object->secrets = array_merge($previousSecrets, $newSecrets);

        // Validate feed source
        $this->validateFeedSource($object, $operation);

        // Update properties.
        $this->updateFeedSourceProperties($feedSource, $object);

        // Set tenant
        $user = $this->security->getUser();
        if (!$user instanceof TenantScopedUserInterface) {
            throw new InvalidArgumentException('The user is not a tenant owner.');
        }
        $feedSource->setTenant($user->getActiveTenant());

        return $feedSource;
    }

    /**
     * @throws UnknownFeedTypeException
     */
    protected function updateFeedSourceProperties(FeedSource $feedSource, object $object): void
    {
        $feedSource->setTitle($object->title);
        $feedSource->setDescription($object->description);

        if (!empty($object->secrets)) {
            $feedSource->setSecrets($object->secrets);
        }

        $feedSource->setFeedType($object->feedType);
        $feedType = $this->feedService->getFeedType($object->feedType);
        $feedSource->setSupportedFeedOutputType($feedType->getSupportedFeedOutputType());
    }

    /**
     * @throws UnknownFeedTypeException
     */
    private function validateFeedSource(object $object, Operation $operation): void
    {
        $validator = $this->prepareValidator();

        // Prepare base feed source validation schema
        $feedSourceValidationSchema = FeedSource::getSchema();

        // Validate base feed source
        $this->executeValidation($object, $validator, $feedSourceValidationSchema);

        // Validate dynamic feed type class
        $feedTypeClassName = $object->feedType;
        $feedType = $this->feedService->getFeedType($feedTypeClassName);

        $feedTypeValidationSchema = $feedType->getSchema();

        // If updating and secrets are not set, don't validate.
        if ($operation instanceof Put && empty($object->secrets)) {
            return;
        }

        // Validate secrets based on specific feed type.
        $secrets = (object) $object->secrets;
        $this->executeValidation($secrets, $validator, $feedTypeValidationSchema);
    }

    private function prepareValidator(): Validator
    {
        $schemaStorage = new SchemaStorage();
        $feedSourceValidationSchema = (object) FeedSource::getSchema();
        $schemaStorage->addSchema('file://contentSchema', $feedSourceValidationSchema);

        return new Validator(new Factory($schemaStorage));
    }

    private function executeValidation(mixed $object, Validator $validator, ?array $schema = null): void
    {
        $validator->validate($object, $schema ?? FeedSource::getSchema());
        if (!$validator->isValid()) {
            throw new InvalidArgumentException($this->getErrorMessage($validator));
        }
    }

    private function getErrorMessage(Validator $validator): string
    {
        return $validator->getErrors()[0]['property'].' '.$validator->getErrors()[0]['message'];
    }
}
