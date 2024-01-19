<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\State\ProviderInterface;
use App\Dto\UserActivationCode as UserActivationCodeOutput;
use App\Entity\Tenant\UserActivationCode;
use App\Repository\UserActivationCodeRepository;

class UserActivationCodeProvider extends AbstractProvider
{
    public function __construct(
        ProviderInterface $collectionProvider,
        UserActivationCodeRepository $entityRepository,
    ) {
        parent::__construct($collectionProvider, $entityRepository);
    }

    public function toOutput(object $object): UserActivationCodeOutput
    {
        assert($object instanceof UserActivationCode);

        $output = new UserActivationCodeOutput();
        $output->id = $object->getId();
        $output->created = \DateTimeImmutable::createFromInterface($object->getCreatedAt());
        $output->modified = \DateTimeImmutable::createFromInterface($object->getModifiedAt());
        $output->createdBy = $object->getCreatedBy();
        $output->modifiedBy = $object->getModifiedBy();
        $output->code = $object->getCode();
        $output->codeExpire = $object->getCodeExpire();
        $output->roles = $object->getRoles();
        $output->username = $object->getUsername();

        return $output;
    }
}
