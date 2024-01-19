<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Tenant\UserActivationCode;
use App\Entity\User;
use App\Entity\UserRoleTenant;
use App\Enum\UserTypeEnum;
use App\Exceptions\BadRequestException;
use App\Exceptions\CodeGenerationException;
use App\Exceptions\ConflictException;
use App\Exceptions\NotFoundException;
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
    public const CODE_ALPHABET = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';

    public function __construct(
        private readonly UserActivationCodeRepository $activationCodeRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly Security $security,
        private readonly string $hashSalt,
        private readonly UserRoleTenantRepository $userRoleTenantRepository,
        private readonly UserRepository $userRepository,
        private readonly string $codeExpireInterval,
    ) {}

    public function generatePersonalIdentifierHash(string $personalIdentifier): string
    {
        return hash('sha512', $this->hashSalt.$personalIdentifier);
    }

    /**
     * @throws CodeGenerationException
     * @throws NotFoundException
     */
    public function refreshCode(string $code): UserActivationCode
    {
        /** @var UserActivationCode $activationCode */
        $activationCode = $this->activationCodeRepository->findOneBy(['code' => $code]);

        if (null == $activationCode) {
            throw new NotFoundException();
        }

        $activationCode->setCode($this->generateExternalUserCode());
        $activationCode->setCodeExpire(\DateTimeImmutable::createFromInterface(new \DateTime())->add(new \DateInterval($this->getCodeExpireInterval())));
        $this->entityManager->flush();

        return $activationCode;
    }

    /**
     * @throws BadRequestException
     * @throws NotFoundException
     * @throws ConflictException
     */
    public function activateExternalUser(string $code): void
    {
        /** @var User $user */
        $user = $this->security->getUser();

        // Make sure user is an external user.
        if (UserTypeEnum::OIDC_EXTERNAL === !$user->getUserType()) {
            throw new BadRequestException('User is not of external type.');
        }

        $activationCode = $this->activationCodeRepository->findOneBy(['code' => $code]);

        if (null === $activationCode) {
            throw new NotFoundException('Activation code not found.');
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

        if (str_ends_with($user->getEmail(), '@ext_not_set')) {
            $user->setEmail($this->getEmailFromDisplayName($displayName));
        }

        // Make sure UserRoleTenant does not already exist.
        $userRoleTenants = $this->userRoleTenantRepository->findBy(['user' => $user, 'tenant' => $tenant]);

        if (count($userRoleTenants) > 0) {
            throw new ConflictException('User already activated for the given tenant.');
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

        return $slugged.'@ext';
    }

    public function slugifyDisplayName(string $displayName): string
    {
        $slugger = new AsciiSlugger();

        return $slugger->slug($displayName, '')->toString();
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
        $charsLength = strlen(self::CODE_ALPHABET);
        $bindKey = '';

        for ($i = 0; $i < $length; ++$i) {
            $bindKey .= self::CODE_ALPHABET[rand(0, $charsLength - 1)];
        }

        return $bindKey;
    }

    /**
     * @throws BadRequestException
     * @throws NotFoundException
     */
    public function removeUserFromCurrentTenant(Ulid $ulid): void
    {
        $user = $this->userRepository->find($ulid);

        if (null === $user) {
            throw new NotFoundException('User not found');
        }

        if (UserTypeEnum::OIDC_EXTERNAL !== $user->getUserType()) {
            throw new BadRequestException('User type cannot be removed from tenant');
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

    public function getCodeExpireInterval(): string
    {
        return $this->codeExpireInterval;
    }
}
