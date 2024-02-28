<?php

declare(strict_types=1);

namespace App\Tests;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Entity\Tenant;
use App\Entity\User;
use App\Entity\UserRoleTenant;
use App\Enum\UserTypeEnum;
use App\Service\UserService;
use App\Utils\IriHelperUtils;
use Hautelook\AliceBundle\PhpUnit\BaseDatabaseTrait;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

abstract class AbstractBaseApiTestCase extends ApiTestCase
{
    use BaseDatabaseTrait;

    protected IriHelperUtils $iriHelperUtils;
    protected JWTTokenManagerInterface $JWTTokenManager;

    protected User $user;
    protected Tenant $tenant;

    public static function setUpBeforeClass(): void
    {
        static::bootKernel();
        static::ensureKernelTestCase();
        if (!filter_var(getenv('API_TEST_CASE_DO_NOT_POPULATE_DATABASE'), FILTER_VALIDATE_BOOL)) {
            static::populateDatabase();
        }
    }

    protected function setUp(): void
    {
        $this->iriHelperUtils = static::getContainer()->get(IriHelperUtils::class);
        $this->JWTTokenManager = static::getContainer()->get(JWTTokenManagerInterface::class);
    }

    protected function getJwtToken(User $user): string
    {
        return $this->JWTTokenManager->create($user);
    }

    /**
     * Get an authenticated client for a user scoped to the 'ABC' tenant loaded from fixtures.
     *
     * @param string $role
     *
     * @return Client
     *
     * @throws \Exception
     */
    protected function getAuthenticatedClient(string $role = 'ROLE_EDITOR'): Client
    {
        $manager = static::getContainer()->get('doctrine')->getManager();

        $user = $manager->getRepository(User::class)->findOneBy(['providerId' => 'test@example.com']);
        $tenant = $manager->getRepository(Tenant::class)->findOneBy(['tenantKey' => 'ABC']);

        if (null === $user) {
            $user = new User();
            $user->setFullName('Test Test');
            $user->setEmail('test@example.com');
            $user->setProviderId('test@example.com');
            $user->setUserType(UserTypeEnum::OIDC_INTERNAL);
            $user->setProvider(self::class);
            $user->setPassword(
                static::getContainer()->get('security.user_password_hasher')->hashPassword($user, '$3CR3T')
            );

            $userRoleTenant = new UserRoleTenant();
            $userRoleTenant->setTenant($tenant);
            $userRoleTenant->setRoles([$role]);

            $user->addUserRoleTenant($userRoleTenant);

            $manager->persist($user);
        }

        $userRoleTenants = $user->getUserRoleTenants();
        /** @var UserRoleTenant $userRoleTenant */
        $userRoleTenant = $userRoleTenants->first();
        $userRoleTenant->setRoles([$role]);
        $manager->flush();

        $user->setActiveTenant($tenant);

        $this->user = $user;
        $this->tenant = $user->getActiveTenant();

        $token = $this->getJwtToken($this->user);

        return static::createClient([], ['auth_bearer' => $token]);
    }

    /**
     * Get an authenticated client for an external user without tenants.
     *
     * @return Client
     */
    protected function getAuthenticatedClientForExternalUser(bool $newUser = false): Client
    {
        $manager = self::getContainer()->get('doctrine')->getManager();

        $providerId = '123456@ext_not_set';

        $user = $manager->getRepository(User::class)->findOneBy(['providerId' => $providerId]);

        if (null !== $user && $newUser) {
            $manager->remove($user);
            $manager->flush();
            $user = null;
        }

        if (null === $user) {
            $user = new User();
            $user->setFullName(UserService::EXTERNAL_USER_DEFAULT_NAME);
            $user->setEmail($providerId);
            $user->setProviderId($providerId);
            $user->setProvider(self::class);
            $user->setUserType(UserTypeEnum::OIDC_EXTERNAL);

            $manager->persist($user);
        }

        $manager->flush();

        $this->user = $user;

        $token = $this->getJwtToken($this->user);

        return static::createClient([], ['auth_bearer' => $token]);
    }
}
