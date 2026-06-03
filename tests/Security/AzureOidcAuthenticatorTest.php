<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Entity\Tenant;
use App\Entity\User;
use App\Enum\UserCreatorEnum;
use App\Enum\UserTypeEnum;
use App\Security\AzureOidcAuthenticator;
use App\Service\TenantFactory;
use App\Service\UserService;
use App\Utils\Roles;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use ItkDev\OpenIdConnect\Exception\MetadataException;
use ItkDev\OpenIdConnectBundle\Security\OpenIdConfigurationProviderManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class AzureOidcAuthenticatorTest extends TestCase
{
    private const string CLAIM_NAME = 'name';
    private const string CLAIM_EMAIL = 'email';
    private const string CLAIM_GROUPS = 'groups';
    private const string CLAIM_SIGN_IN_NAME = 'signinname';

    /**
     * authenticate() must convert any OIDC failure raised while validating the
     * claims into a CustomUserMessageAuthenticationException so the firewall
     * can render a clean auth failure.
     *
     * Regression guard for the openid-connect 5.0 exception rework: the catch
     * matches on OpenIdConnectExceptionInterface (which MetadataException
     * implements). Before the upgrade this caught the deprecated abstract
     * ItkOpenIdConnectException, which the new concrete exceptions no longer
     * extend — so the failure would have escaped uncaught.
     */
    public function testAuthenticateWrapsOidcExceptionAsAuthenticationException(): void
    {
        $authenticator = $this->getMockBuilder(AzureOidcAuthenticator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validateClaims'])
            ->getMock();
        $authenticator->method('validateClaims')
            ->willThrowException(new MetadataException('malformed discovery document'));
        $authenticator->setLogger(new NullLogger());

        $request = Request::create('/v2/authentication/oidc/token?state=test-state&code=test-code');

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('malformed discovery document');

        $authenticator->authenticate($request);
    }

    /**
     * #2 — internal provider, new user: the user is provisioned from the
     * claims (full name, email, provider id == email, internal type, OIDC
     * creator) and the configured authenticator is recorded as the provider.
     */
    public function testAuthenticateProvisionsNewInternalUserFromClaims(): void
    {
        $persisted = [];
        $em = $this->createEntityManager(null, $persisted);

        $authenticator = $this->createAuthenticator([
            'open_id_connect_provider' => AzureOidcAuthenticator::OIDC_PROVIDER_INTERNAL,
            self::CLAIM_NAME => 'Jane Doe',
            self::CLAIM_EMAIL => 'jane@example.test',
            self::CLAIM_GROUPS => [],
        ], $em);

        $passport = $authenticator->authenticate(Request::create('/'));

        $this->assertInstanceOf(SelfValidatingPassport::class, $passport);
        $user = $this->onlyPersistedUser($persisted);
        $this->assertSame('Jane Doe', $user->getFullName());
        $this->assertSame('jane@example.test', $user->getEmail());
        $this->assertSame('jane@example.test', $user->getProviderId());
        $this->assertSame(UserTypeEnum::OIDC_INTERNAL, $user->getUserType());
        $this->assertSame(UserCreatorEnum::OIDC->value, $user->getCreatedBy());
        $this->assertSame(AzureOidcAuthenticator::class, $user->getProvider());
    }

    /**
     * #1 — group claims map to tenant keys + roles: the "Admin" suffix grants
     * ROLE_ADMIN, the "Redaktoer" suffix grants ROLE_EDITOR, and the tenant key
     * is the group name with the suffix stripped.
     */
    public function testAuthenticateMapsGroupClaimsToTenantRoles(): void
    {
        $persisted = [];
        $em = $this->createEntityManager(null, $persisted);

        $authenticator = $this->createAuthenticator([
            'open_id_connect_provider' => AzureOidcAuthenticator::OIDC_PROVIDER_INTERNAL,
            self::CLAIM_NAME => 'Jane Doe',
            self::CLAIM_EMAIL => 'jane@example.test',
            self::CLAIM_GROUPS => ['Example1Admin', 'Example2Redaktoer'],
        ], $em);

        $authenticator->authenticate(Request::create('/'));

        $this->assertSame([
            'Example1' => [Roles::ROLE_ADMIN],
            'Example2' => [Roles::ROLE_EDITOR],
        ], $this->tenantRoleMap($this->onlyPersistedUser($persisted)));
    }

    /**
     * #1 — multiple groups for the same tenant accumulate into a single
     * tenant entry carrying both roles.
     */
    public function testAuthenticateAccumulatesMultipleRolesForSameTenant(): void
    {
        $persisted = [];
        $em = $this->createEntityManager(null, $persisted);

        $authenticator = $this->createAuthenticator([
            'open_id_connect_provider' => AzureOidcAuthenticator::OIDC_PROVIDER_INTERNAL,
            self::CLAIM_NAME => 'Jane Doe',
            self::CLAIM_EMAIL => 'jane@example.test',
            self::CLAIM_GROUPS => ['Example1Admin', 'Example1Redaktoer'],
        ], $em);

        $authenticator->authenticate(Request::create('/'));

        $this->assertSame([
            'Example1' => [Roles::ROLE_ADMIN, Roles::ROLE_EDITOR],
        ], $this->tenantRoleMap($this->onlyPersistedUser($persisted)));
    }

    /**
     * #1 — de-provisioning: tenants no longer present in the claims are
     * revoked from an existing user, while a tenant still present has its
     * roles refreshed from the claims (here EDITOR -> ADMIN).
     */
    public function testAuthenticateRevokesTenantsAbsentFromClaims(): void
    {
        $keep = (new Tenant())->setTenantKey('Example1');
        $stale = (new Tenant())->setTenantKey('OldTenant');

        $existing = new User();
        $existing->setEmail('jane@example.test');
        $existing->setProviderId('jane@example.test');
        $existing->setRoleTenant([Roles::ROLE_EDITOR], $keep);
        $existing->setRoleTenant([Roles::ROLE_ADMIN], $stale);

        $persisted = [];
        $em = $this->createEntityManager($existing, $persisted);

        // The factory must return the same managed Tenant instances the user
        // already references, mirroring TenantRepository::findByKeys() so the
        // existing role-tenant link is updated rather than duplicated.
        $authenticator = $this->createAuthenticator([
            'open_id_connect_provider' => AzureOidcAuthenticator::OIDC_PROVIDER_INTERNAL,
            self::CLAIM_NAME => 'Jane Doe',
            self::CLAIM_EMAIL => 'jane@example.test',
            self::CLAIM_GROUPS => ['Example1Admin'],
        ], $em, ['Example1' => $keep, 'OldTenant' => $stale]);

        $authenticator->authenticate(Request::create('/'));

        $this->assertSame([
            'Example1' => [Roles::ROLE_ADMIN],
        ], $this->tenantRoleMap($existing));
    }

    /**
     * #1 — a group whose name carries neither the admin nor the editor suffix
     * is a configuration/claim error and aborts authentication with an
     * \InvalidArgumentException (it is not one of the OIDC exception types the
     * authenticator wraps).
     */
    public function testAuthenticateThrowsOnGroupWithUnknownRoleSuffix(): void
    {
        $persisted = [];
        $em = $this->createEntityManager(null, $persisted);

        $authenticator = $this->createAuthenticator([
            'open_id_connect_provider' => AzureOidcAuthenticator::OIDC_PROVIDER_INTERNAL,
            self::CLAIM_NAME => 'Jane Doe',
            self::CLAIM_EMAIL => 'jane@example.test',
            self::CLAIM_GROUPS => ['Example1Viewer'],
        ], $em);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown role for group: Example1Viewer');

        $authenticator->authenticate(Request::create('/'));
    }

    /**
     * #2 — external provider, new user: the user is keyed by a hash of the
     * sign-in name, gets a placeholder email/name to be filled in on
     * activation, the external type, and is assigned no tenant roles.
     */
    public function testAuthenticateProvisionsNewExternalUser(): void
    {
        $persisted = [];
        $em = $this->createEntityManager(null, $persisted);

        $userService = $this->createMock(UserService::class);
        $userService->method('generatePersonalIdentifierHash')->with('ext-sign-in')->willReturn('hashed-id');

        $authenticator = $this->createAuthenticator([
            'open_id_connect_provider' => AzureOidcAuthenticator::OIDC_PROVIDER_EXTERNAL,
            self::CLAIM_SIGN_IN_NAME => 'ext-sign-in',
        ], $em, [], $userService);

        $authenticator->authenticate(Request::create('/'));

        $user = $this->onlyPersistedUser($persisted);
        $this->assertSame('hashed-id', $user->getProviderId());
        $this->assertSame('hashed-id@ext_not_set', $user->getEmail());
        $this->assertSame(UserService::EXTERNAL_USER_DEFAULT_NAME, $user->getFullName());
        $this->assertSame(UserTypeEnum::OIDC_EXTERNAL, $user->getUserType());
        $this->assertCount(0, $user->getUserRoleTenants());
    }

    /**
     * #2 — external provider with a missing/empty required id claim fails with
     * a CustomUserMessageAuthenticationException naming the missing claim.
     */
    public function testAuthenticateRejectsExternalUserWithMissingIdClaim(): void
    {
        $persisted = [];
        $em = $this->createEntityManager(null, $persisted);

        $authenticator = $this->createAuthenticator([
            'open_id_connect_provider' => AzureOidcAuthenticator::OIDC_PROVIDER_EXTERNAL,
            self::CLAIM_SIGN_IN_NAME => '',
        ], $em);

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('Missing required claim '.self::CLAIM_SIGN_IN_NAME);

        $authenticator->authenticate(Request::create('/'));
    }

    /**
     * #2 — an unrecognised open_id_connect_provider value is rejected with a
     * CustomUserMessageAuthenticationException.
     */
    public function testAuthenticateRejectsUnsupportedProvider(): void
    {
        $persisted = [];
        $em = $this->createEntityManager(null, $persisted);

        $authenticator = $this->createAuthenticator([
            'open_id_connect_provider' => 'unsupported',
        ], $em);

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('Unsupported open_id_connect_provider.');

        $authenticator->authenticate(Request::create('/'));
    }

    /**
     * Builds an AzureOidcAuthenticator with real collaborators but a stubbed
     * validateClaims() so tests can drive the post-validation logic directly.
     *
     * @param array<string, mixed>   $claims    claims validateClaims() returns
     * @param array<string, Tenant>  $tenantSeed tenant key => instance the
     *                                            TenantFactory should hand back
     */
    private function createAuthenticator(
        array $claims,
        EntityManagerInterface $em,
        array $tenantSeed = [],
        ?UserService $userService = null,
    ): AzureOidcAuthenticator {
        $authenticator = $this->getMockBuilder(AzureOidcAuthenticator::class)
            ->setConstructorArgs([
                $em,
                $this->createMock(OpenIdConfigurationProviderManager::class),
                $this->createTenantFactory($tenantSeed),
                $userService ?? $this->createMock(UserService::class),
                self::CLAIM_NAME,
                self::CLAIM_EMAIL,
                self::CLAIM_GROUPS,
                self::CLAIM_SIGN_IN_NAME,
            ])
            ->onlyMethods(['validateClaims'])
            ->getMock();
        $authenticator->method('validateClaims')->willReturn($claims);
        $authenticator->setLogger(new NullLogger());

        return $authenticator;
    }

    /**
     * @param list<object> $persisted captures everything persist()ed
     */
    private function createEntityManager(?User $found, array &$persisted): EntityManagerInterface
    {
        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findOneBy')->willReturn($found);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repository);
        $em->method('persist')->willReturnCallback(static function (object $entity) use (&$persisted): void {
            $persisted[] = $entity;
        });

        return $em;
    }

    /**
     * Mirrors TenantFactory::setupTenants(): returns the seeded instance for a
     * known key, otherwise a fresh Tenant — so role assignment reuses managed
     * instances exactly as production does.
     *
     * @param array<string, Tenant> $seed
     */
    private function createTenantFactory(array $seed): TenantFactory
    {
        $factory = $this->createMock(TenantFactory::class);
        $factory->method('setupTenants')->willReturnCallback(
            static function (array $tenantKeys) use ($seed): array {
                $tenants = [];
                foreach ($tenantKeys as $tenantKey) {
                    $tenants[$tenantKey] = $seed[$tenantKey] ?? (new Tenant())->setTenantKey($tenantKey);
                }

                return $tenants;
            }
        );

        return $factory;
    }

    /**
     * @param list<object> $persisted
     */
    private function onlyPersistedUser(array $persisted): User
    {
        $users = array_values(array_filter($persisted, static fn (object $e): bool => $e instanceof User));
        $this->assertCount(1, $users, 'Expected exactly one User to be persisted.');

        return $users[0];
    }

    /**
     * @return array<string, array<int, string>> tenant key => roles
     */
    private function tenantRoleMap(User $user): array
    {
        $map = [];
        foreach ($user->getUserRoleTenants() as $userRoleTenant) {
            $map[$userRoleTenant->getTenant()->getTenantKey()] = $userRoleTenant->getRoles();
        }

        return $map;
    }
}
