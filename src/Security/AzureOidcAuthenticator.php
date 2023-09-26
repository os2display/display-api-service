<?php

namespace App\Security;

use App\Entity\User;
use App\Enum\UserTypeEnum;
use App\Service\ExternalUserService;
use App\Service\TenantFactory;
use Doctrine\ORM\EntityManagerInterface;
use ItkDev\OpenIdConnect\Exception\ItkOpenIdConnectException;
use ItkDev\OpenIdConnectBundle\Exception\InvalidProviderException;
use ItkDev\OpenIdConnectBundle\Security\OpenIdConfigurationProviderManager;
use ItkDev\OpenIdConnectBundle\Security\OpenIdLoginAuthenticator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class AzureOidcAuthenticator extends OpenIdLoginAuthenticator
{
    public const OIDC_PROVIDER_INTERNAL = 'internal';
    public const OIDC_PROVIDER_EXTERNAL = 'external';

    public const OIDC_POSTFIX_ADMIN_KEY = 'Admin';
    public const OIDC_POSTFIX_EDITOR_KEY = 'Redaktoer';

    public const APP_ADMIN_ROLE = 'ROLE_ADMIN';
    public const APP_EDITOR_ROLE = 'ROLE_EDITOR';

    private EntityManagerInterface $entityManager;
    private TenantFactory $tenantFactory;

    public function __construct(
        EntityManagerInterface $entityManager,
        OpenIdConfigurationProviderManager $providerManager,
        TenantFactory $tenantFactory,
        private readonly ExternalUserService $externalUserService,
    ) {
        parent::__construct($providerManager);

        $this->entityManager = $entityManager;
        $this->tenantFactory = $tenantFactory;
    }

    /**
     * @throws InvalidProviderException
     */
    public function authenticate(Request $request): Passport
    {
        try {
            // Validate claims
            $claims = $this->validateClaims($request);

            $provider = $claims['open_id_connect_provider'];

            switch ($provider) {
                case self::OIDC_PROVIDER_EXTERNAL:
                    $signInName = $claims['signinname'];

                    if (empty($signInName)) {
                        throw new CustomUserMessageAuthenticationException('Missing required claim signinname');
                    }

                    $type = UserTypeEnum::OIDC_EXTERNAL;
                    $name = ExternalUserService::EXTERNAL_USER_DEFAULT_NAME;
                    $email = $this->externalUserService->generateEmailFromPersonalIdentifier($signInName);
                    break;
                case self::OIDC_PROVIDER_INTERNAL:
                    $type = UserTypeEnum::OIDC_ACTIVE_DIRECTORY;
                    $name = $claims['navn'];
                    $email = $claims['email'];
                    break;
                default:
                    throw new CustomUserMessageAuthenticationException('Unsupported open_id_connect_provider.');
            }

            // Check if user exists already - if not create a user
            $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

            if (null === $user) {
                // Create the new user and persist it
                $user = new User();
                $user->setCreatedBy('OIDC');
                $user->setEmail($email);
                $user->setFullName($name);

                $this->entityManager->persist($user);
            }

            $user->setProvider(self::class);
            $user->setUserType($type);

            if (self::OIDC_PROVIDER_INTERNAL === $provider) {
                // Set tenants from AD claims.
                $oidcGroups = $claims['groups'] ?? [];
                $this->setTenantRoles($user, $oidcGroups);
            }

            $this->entityManager->flush();

            return new SelfValidatingPassport(new UserBadge($user->getUserIdentifier(), [$this, 'getUser']));
        } catch (ItkOpenIdConnectException $exception) {
            throw new CustomUserMessageAuthenticationException($exception->getMessage());
        }
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return new Response('Auth success', 200);
    }

    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        return new Response('Auth header required', 401);
    }

    public function getUser(string $identifier): User
    {
        return $this->entityManager->getRepository(User::class)->findOneBy(['email' => $identifier]);
    }

    private function setTenantRoles(User $user, array $oidcGroups): void
    {
        $tenantKeyRoleMap = $this->mapTenantKeysRoles($oidcGroups);
        $tenantKeys = \array_keys($tenantKeyRoleMap);

        $this->cleanUserTenants($user, $tenantKeys);

        $tenants = $this->tenantFactory->setupTenants($tenantKeys, 'OIDC');
        $tenantKeyRoleMap = $this->mapTenantKeysRoles($oidcGroups);

        foreach ($tenantKeyRoleMap as $tenantKey => $roles) {
            $user->setRoleTenant($roles, $tenants[$tenantKey]);
        }

        $this->entityManager->flush();
    }

    private function cleanUserTenants(User $user, array $tenantKeys): void
    {
        foreach ($user->getUserRoleTenants() as $userRoleTenant) {
            if (!in_array($userRoleTenant->getTenant()->getTenantKey(), $tenantKeys)) {
                $user->removeUserRoleTenant($userRoleTenant);
            }
        }
    }

    private function mapTenantKeysRoles(array $oidcGroups): array
    {
        $tenantKeyRoleMap = [];

        foreach ($oidcGroups as $oidcGroup) {
            try {
                list($tenantKey, $role) = $this->getTenantKeyWithRole($oidcGroup);

                if (!array_key_exists($tenantKey, $tenantKeyRoleMap)) {
                    $tenantKeyRoleMap[$tenantKey] = [];
                }

                $tenantKeyRoleMap[$tenantKey][] = $role;
            } catch (\InvalidArgumentException $e) {
                // @TODO Should we log, ignore or throw exception if unknown role is encountered?
            }
        }

        return $tenantKeyRoleMap;
    }

    private function getTenantKeyWithRole(string $oidcGroup): array
    {
        if (str_ends_with($oidcGroup, self::OIDC_POSTFIX_ADMIN_KEY)) {
            $tenantKey = \str_replace(self::OIDC_POSTFIX_ADMIN_KEY, '', $oidcGroup);
            $role = self::APP_ADMIN_ROLE;

            return [$tenantKey, $role];
        }

        if (str_ends_with($oidcGroup, self::OIDC_POSTFIX_EDITOR_KEY)) {
            $tenantKey = \str_replace(self::OIDC_POSTFIX_EDITOR_KEY, '', $oidcGroup);
            $role = self::APP_EDITOR_ROLE;

            return [$tenantKey, $role];
        }

        throw new \InvalidArgumentException('Unknown role for group: '.$oidcGroup);
    }
}
