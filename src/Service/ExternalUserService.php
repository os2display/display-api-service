<?php

namespace App\Service;

use App\Entity\ExternalUserActivationCode;
use App\Entity\User;
use App\Entity\UserRoleTenant;
use App\Enum\UserTypeEnum;
use App\Exceptions\CodeGenerationException;
use App\Exceptions\ExternalUserCodeException;
use App\Repository\ExternalUserActivationCodeRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactory;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Security\Core\Security;

class ExternalUserService
{
    public function __construct(
        private readonly ExternalUserActivationCodeRepository $activationCodeRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly Security $security,
        private readonly string $hashSalt,
    ) {}

    public function generateEmailFromPersonalIdentifier(string $personalIdentifier): string
    {
        $hash = hash('sha512', $this->hashSalt . $personalIdentifier);

        return "$hash@external";
    }

    /**
     * @throws CodeGenerationException
     */
    public function refreshCode(ExternalUserActivationCode $code): ExternalUserActivationCode
    {
        $code->setCode($this->generateExternalUserCode());
        $code->setCodeExpire((new \DateTime())->add(new \DateInterval('P2D')));
        $this->entityManager->flush();

        return $code;
    }

    /**
     * @throws ExternalUserCodeException
     */
    public function activateExternalUser(string $code): void
    {
        $activationCode = $this->activationCodeRepository->findOneBy(['code' => $code]);

        if ($activationCode === null) {
            throw new ExternalUserCodeException("Activation code not found.");
        }

        /** @var User $user */
        $user = $this->security->getUser();

        // Make sure user is an external user.
        if (!$user->getUserType() === UserTypeEnum::OIDC_EXTERNAL) {
            throw new ExternalUserCodeException("User is not an external type.");
        }

        // Set user's fullName if not set.
        if (empty($user->getFullName()) || $user->getFullName() === 'UNKNOWN') {
            $user->setFullName($activationCode->getUsername());
        }

        // TODO: Make sure UserRoleTenant does not already exist.

        $userRoleTenant = new UserRoleTenant();
        $userRoleTenant->setTenant($activationCode->getTenant());
        $userRoleTenant->setRoles($activationCode->getRoles());

        $user->addUserRoleTenant($userRoleTenant);

        $this->entityManager->persist($userRoleTenant);

        $this->entityManager->flush();
    }

    /**
     * @throws CodeGenerationException
     */
    public function generateExternalUserCode(): string
    {
        $i = 0;

        do {
            $code = $this->generateRandomCode();

            $usersWithCode = $this->activationCodeRepository->findBy(['code' => $code]);

            if (count($usersWithCode) === 0) {
                return $code;
            }

            $i++;
        } while ($i < 100);

        throw new CodeGenerationException("Could not generate unique code.");
    }

    private function generateRandomCode(): string
    {
        $length = 12;
        $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charsLength = strlen($chars);
        $bindKey = '';

        for ($i = 0; $i < $length; ++$i) {
            $bindKey .= $chars[rand(0, $charsLength - 1)];
        }

        return $bindKey;
    }
}
