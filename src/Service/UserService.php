<?php

namespace App\Service;

use App\Entity\Tenant\UserActivationCode;
use App\Entity\User;
use App\Entity\UserRoleTenant;
use App\Enum\UserTypeEnum;
use App\Exceptions\CodeGenerationException;
use App\Exceptions\UserException;
use App\Repository\UserActivationCodeRepository;
use App\Repository\UserRepository;
use App\Repository\UserRoleTenantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\Uid\Ulid;

class UserService
{
    public const EXTERNAL_USER_DEFAULT_NAME = 'EXTERNAL_NOT_SET';

    public function __construct(
        private readonly UserActivationCodeRepository $activationCodeRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly Security $security,
        private readonly string $hashSalt,
        private readonly UserRoleTenantRepository $userRoleTenantRepository,
        private readonly UserRepository $userRepository,
    ) {}

    public function generatePersonalIdentifierHash(string $personalIdentifier): string
    {
        return hash('sha512', $this->hashSalt.$personalIdentifier);
    }

    /**
     * @throws CodeGenerationException
     */
    public function refreshCode(UserActivationCode $code): UserActivationCode
    {
        $code->setCode($this->generateExternalUserCode());
        $code->setCodeExpire((new \DateTime())->add(new \DateInterval('P2D')));
        $this->entityManager->flush();

        return $code;
    }

    /**
     * @throws UserException
     */
    public function activateExternalUser(string $code): void
    {
        /** @var User $user */
        $user = $this->security->getUser();

        // Make sure user is an external user.
        if (UserTypeEnum::OIDC_EXTERNAL === !$user->getUserType()) {
            throw new UserException('User is not an external type.', 404);
        }

        $activationCode = $this->activationCodeRepository->findOneBy(['code' => $code]);

        if (null === $activationCode) {
            throw new UserException('Activation code not found.', 404);
        }

        $tenant = $activationCode->getTenant();
        $roles = $activationCode->getRoles();
        $displayName = $activationCode->getUsername();

        // The activation code has been used. Remove it.
        $this->activationCodeRepository->remove($activationCode, true);

        // Set user's fullName if not set.
        if (empty($user->getFullName()) || self::EXTERNAL_USER_DEFAULT_NAME === $user->getFullName()) {
            $user->setFullName($displayName);
        }

        if (null === $user->getEmail()) {
            $user->setEmail($this->getEmailFromDisplayName($displayName));
        }

        // Make sure UserRoleTenant does not already exist.
        $userRoleTenants = $this->userRoleTenantRepository->findBy(['user' => $user, 'tenant' => $tenant]);

        if (count($userRoleTenants) > 0) {
            throw new UserException('User already activated for the given tenant.', 400);
        }

        $userRoleTenant = new UserRoleTenant();
        $userRoleTenant->setTenant($tenant);
        $userRoleTenant->setRoles($roles);

        $user->addUserRoleTenant($userRoleTenant);

        $this->entityManager->persist($userRoleTenant);

        $this->entityManager->flush();
    }

    public function getEmailFromDisplayName(string $displayName): string
    {
        $slugged = $this->slugifyDisplayName($displayName);

        return "$slugged@ext";
    }

    public function slugifyDisplayName(string $displayName): string
    {
        $slugger = new AsciiSlugger();

        return $slugger->slug($displayName, '');
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

            if (0 === count($usersWithCode)) {
                return $code;
            }

            ++$i;
        } while ($i < 100);

        throw new CodeGenerationException('Could not generate unique code.');
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

    /**
     * @throws UserException
     */
    public function removeUserFromCurrentTenant(Ulid $ulid): void
    {
        $user = $this->userRepository->find($ulid);

        if (null === $user) {
            throw new UserException('User not found', 404);
        }

        if (UserTypeEnum::OIDC_EXTERNAL !== $user->getUserType()) {
            throw new UserException('User type cannot be removed from tenant', 400);
        }

        /** @var User $currentUser */
        $currentUser = $this->security->getUser();
        $activeTenant = $currentUser->getActiveTenant();

        $found = $this->userRoleTenantRepository->findBy(['user' => $ulid, 'tenant' => $activeTenant]);

        foreach ($found as $userRoleTenant) {
            $this->entityManager->remove($userRoleTenant);
        }

        $this->entityManager->flush();
    }
}
