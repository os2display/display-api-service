<?php

namespace App\DataTransformer;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use App\Dto\UserOutput;
use App\Entity\User;
use App\Entity\UserRoleTenant;
use Symfony\Component\Security\Core\Security;

class UserOutputDataTransformer implements DataTransformerInterface
{
    public function __construct(
        private readonly Security $security
    ) {}

    /**
     * {@inheritdoc}
     *
     * @var User
     */
    public function transform($object, string $to, array $context = []): UserOutput
    {
        $output = new UserOutput();
        $output->fullName = $object->getFullName();
        $output->userType = $object->getUserType();
        $output->createdAt = $object->getCreatedAt();

        /** @var User $currentUser */
        $currentUser = $this->security->getUser();
        $activeTenant = $currentUser->getActiveTenant();

        $output->roles = array_unique(array_reduce($object->getUserRoleTenants()->toArray(), function ($carry, UserRoleTenant $userRoleTenant) use ($activeTenant) {
            if ($userRoleTenant->getTenant() == $activeTenant) {
                $carry += $userRoleTenant->getRoles();
            }

            return $carry;
        }, []));

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
