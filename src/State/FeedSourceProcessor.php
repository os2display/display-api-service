<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Exception\InvalidArgumentException;
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

        $this->validateFeedSource($object);

        return $feedSource;
    }

    private function validateFeedSource(object $object): void
    {
        $title = $object->title;

        // Check title isset
        if (empty($title) || !is_string($title)) {
            throw new InvalidArgumentException('A feed source must have a title');
        }

        $description = $object->description;

        // Check description isset
        if (empty($description) || !is_string($description)) {
            throw new InvalidArgumentException('A feed source must have a description');
        }

        $feedType = $object->feedType;

        // Check feedType isset
        if (empty($feedType) || !is_string($feedType)) {
            throw new InvalidArgumentException('A feed source must have a type');
        }

        switch ($object->feedType) {
            case 'App\\Feed\\EventDatabaseApiFeedType':
                $host = $object->secrets[0]['host'];
                $patternWithoutProtocol = '^((?!-)[A-Za-z0-9-]{1,63}(?<!-)\\.)+[A-Za-z]{2,6}$';
                $patternWithProtocol = 'https?:\/\/(www\.)?[-a-zA-Z0-9@:%._\+~#=]{1,256}\.[a-zA-Z0-9()]{1,6}\b([-a-zA-Z0-9()@:%_\+.~#?&//=]*)';

                // Check host isset
                if (empty($host) || !is_string($host)) {
                    throw new InvalidArgumentException('This feed source type must have a host defined');
                }

                // Check host valid url
                if (!preg_match("`$patternWithProtocol`", $host)) {
                    if (!preg_match("`$patternWithoutProtocol`", $host)) {
                        throw new InvalidArgumentException('The host must be a valid URL');
                    } else {
                        throw new InvalidArgumentException('The host must be a valid URL including http or https');
                    }
                }
                break;
            case "App\Feed\NotifiedFeedType":
                $token = $object->secrets[0]['token'];

                // Check token isset
                if (!isset($token) || !is_string($token)) {
                    throw new InvalidArgumentException('This feed source type must have a token defined');
                }
                break;
            case '':
                break;
        }
    }
}
