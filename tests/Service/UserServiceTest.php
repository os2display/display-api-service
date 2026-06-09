<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\User;
use App\Enum\UserTypeEnum;
use App\Exceptions\BadRequestException;
use App\Exceptions\NotFoundException;
use App\Repository\UserActivationCodeRepository;
use App\Repository\UserRepository;
use App\Repository\UserRoleTenantRepository;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Security;

class UserServiceTest extends TestCase
{
    public function testActivateExternalUserThrowsForNonExternalUser(): void
    {
        $user = new User();
        $user->setUserType(UserTypeEnum::OIDC_INTERNAL);

        $userService = $this->createUserService($user);

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('User is not of external type.');

        $userService->activateExternalUser('SOMECODE');
    }

    public function testActivateExternalUserThrowsForUnknownCode(): void
    {
        $user = new User();
        $user->setUserType(UserTypeEnum::OIDC_EXTERNAL);

        $userService = $this->createUserService($user);

        $this->expectException(NotFoundException::class);

        $userService->activateExternalUser('UNKNOWNCODE');
    }

    private function createUserService(User $user): UserService
    {
        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn($user);

        $activationCodeRepository = $this->createMock(UserActivationCodeRepository::class);
        $activationCodeRepository->method('findOneBy')->willReturn(null);

        return new UserService(
            $activationCodeRepository,
            $this->createMock(EntityManagerInterface::class),
            $security,
            'hash-salt',
            $this->createMock(UserRoleTenantRepository::class),
            $this->createMock(UserRepository::class),
            'P2D',
        );
    }
}
