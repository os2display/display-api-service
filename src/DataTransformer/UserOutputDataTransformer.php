<?php

namespace App\DataTransformer;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use App\Dto\ExternalUserOutput;
use App\Entity\User;

class UserOutputDataTransformer implements DataTransformerInterface
{
    /**
     * {@inheritdoc}
     *
     * @var User
     */
    public function transform($object, string $to, array $context = []): ExternalUserOutput
    {
        $output = new ExternalUserOutput();
        $output->fullName = $object->getFullName();
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
