<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\State\ProviderInterface;
use App\Dto\UserOutput;
use App\Entity\User;
use App\Entity\UserRoleTenant;
use App\Repository\ScreenRepository;
use Symfony\Bundle\SecurityBundle\Security;

class UserProvider extends AbstractProvider
{
    public function __construct(
        private readonly ProviderInterface $collectionProvider,
        private readonly ScreenRepository $entityRepository,
        private readonly Security $security,
    ) {
        parent::__construct($collectionProvider, $entityRepository);
    }

    public function toOutput(object $object): UserOutput
    {
        assert($object instanceof User);

        $output = new UserOutput();
        $output->id = $object->getId();
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
}
