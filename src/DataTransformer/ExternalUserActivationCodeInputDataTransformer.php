<?php

namespace App\DataTransformer;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use App\Dto\UserActivationCodeInput;
use App\Entity\Tenant\UserActivationCode;
use App\Entity\User;
use App\Exceptions\CodeGenerationException;
use App\Repository\UserActivationCodeRepository;
use App\Repository\UserRepository;
use App\Service\UserService;
use App\Utils\Roles;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Core\Security;

class ExternalUserActivationCodeInputDataTransformer implements DataTransformerInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly UserService $userService,
        private readonly UserRepository $userRepository,
        private readonly UserActivationCodeRepository $userActivationCodeRepository,
    ) {}

    /**
     * {@inheritdoc}
     *
     * @throws CodeGenerationException
     *
     * @var UserActivationCodeInput
     */
    public function transform($object, string $to, array $context = []): UserActivationCode
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

    /**
     * {@inheritdoc}
     */
    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        return UserActivationCode::class === $to && ($context['input']['class'] ?? null) === UserActivationCodeInput::class;
    }
}
