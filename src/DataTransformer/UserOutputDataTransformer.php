<?php

namespace App\DataTransformer;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use App\Dto\Template as TemplateDTO;
use App\Dto\ExternalUserOutput;
use App\Entity\Template;
use App\Entity\User;

class UserOutputDataTransformer implements DataTransformerInterface
{
    /**
     * {@inheritdoc}
     *
     * @var User $object
     */
    public function transform($object, string $to, array $context = []): ExternalUserOutput
    {
        $output = new ExternalUserOutput();
        $output->fullName = $object->getFullName();
        $output->externalUserCode = $object->getExternalUserCode();
        $output->externalUserExpire = $object->getExternalUserCodeExpire();
        $output->userType = $object->getUserType();
        return $output;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        return ExternalUserOutput::class === $to && $data instanceof User;
    }
}
