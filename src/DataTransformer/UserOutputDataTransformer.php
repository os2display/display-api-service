<?php

namespace App\DataTransformer;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use App\Dto\Template as TemplateDTO;
use App\Dto\UserOutput;
use App\Entity\Template;
use App\Entity\User;

class UserOutputDataTransformer implements DataTransformerInterface
{
    /**
     * {@inheritdoc}
     *
     * @var User $object
     */
    public function transform($object, string $to, array $context = []): UserOutput
    {
        $output = new UserOutput();
        $output->fullName = $object->getFullName();
        $output->externalUserCode = $object->getExternalUserCode();
        $output->externalUserExpire = $object->getExternalUserCodeExpire();
        $output->userTypeEnum = $object->getUserTypeEnum();
        return $output;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        return UserOutput::class === $to && $data instanceof User;
    }
}
