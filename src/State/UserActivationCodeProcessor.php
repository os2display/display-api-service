<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Tenant\UserActivationCode;
use App\Entity\User;
use App\Repository\UserActivationCodeRepository;
use App\Repository\UserRepository;
use App\Service\UserService;
use App\Utils\IriHelperUtils;
use App\Utils\Roles;
use Doctrine\ORM\EntityManagerInterface;
use HttpException;
use Symfony\Bundle\SecurityBundle\Security;

class UserActivationCodeProcessor extends AbstractProcessor
{
    public function __construct(
        private readonly UserService $userService,
        private readonly UserRepository $userRepository,
        private readonly Security $security,
        private readonly UserActivationCodeRepository $userActivationCodeRepository,
        EntityManagerInterface $entityManager,
        ProcessorInterface $persistProcessor,
        ProcessorInterface $removeProcessor
    )
    {
        parent::__construct($entityManager, $persistProcessor, $removeProcessor);
    }

    protected function fromInput(mixed $object, Operation $operation, array $uriVariables, array $context): UserActivationCode
    {
        /** @var User $user */
        $user = $this->security->getUser();

        $roles = [];

        // Only allow EXTERNAL_USER roles.
        if (in_array(Roles::ROLE_EXTERNAL_USER_ADMIN, $object->roles)) {
            $roles[] = Roles::ROLE_EXTERNAL_USER_ADMIN;
        } else {
            $roles[] = Roles::ROLE_EXTERNAL_USER;
        }

        $code = new UserActivationCode();
        $code->setCode($this->userService->generateExternalUserCode());
        $code->setTenant($user->getActiveTenant());
        $code->setCodeExpire((new \DateTime())->add(new \DateInterval($this->userService->getCodeExpireInterval())));

        $displayName = $object->displayName;
        $email = $this->userService->getEmailFromDisplayName($displayName);

        $code->setUsername($displayName);

        // Make sure username and email are not already in use
        $usersFound = $this->userRepository->findBy(['email' => $email]);
        $usersFoundByFullName = $this->userRepository->findBy(['fullName' => $displayName]);
        $codesFound = $this->userActivationCodeRepository->findBy(['username' => $displayName]);

        if (count($usersFound) > 0 || count($usersFoundByFullName) > 0 || count($codesFound) > 0) {
            throw new HttpException(400, 'Display name is already in use');
        }

        $code->setRoles($roles);

        return $code;
    }
}
