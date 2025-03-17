<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use App\Enum\UserCreatorEnum;
use App\Enum\UserTypeEnum;
use App\Service\TenantFactory;
use App\Service\UserService;
use App\Utils\Roles;
use Doctrine\ORM\EntityManagerInterface;
use ItkDev\OpenIdConnect\Exception\ItkOpenIdConnectException;
use ItkDev\OpenIdConnectBundle\Exception\InvalidProviderException;
use ItkDev\OpenIdConnectBundle\Security\OpenIdConfigurationProviderManager;
use ItkDev\OpenIdConnectBundle\Security\OpenIdLoginAuthenticator;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class AzureOidcAuthenticator extends OpenIdLoginAuthenticator implements LoggerAwareInterface
{
    final public const string OIDC_PROVIDER_INTERNAL = 'internal';
    final public const string OIDC_PROVIDER_EXTERNAL = 'external';

    final public const string OIDC_POSTFIX_ADMIN_KEY = 'Admin';
    final public const string OIDC_POSTFIX_EDITOR_KEY = 'Redaktoer';

    final public const string APP_ADMIN_ROLE = Roles::ROLE_ADMIN;
    final public const string APP_EDITOR_ROLE = Roles::ROLE_EDITOR;

    /** @psalm-suppress PropertyNotSetInConstructor */
    private LoggerInterface $logger;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly OpenIdConfigurationProviderManager $providerManager,
        private readonly TenantFactory $tenantFactory,
        private readonly UserService $externalUserService,
        private readonly string $oidcInternalClaimName,
        private readonly string $oidcInternalClaimEmail,
        private readonly string $oidcInternalClaimGroups,
        private readonly string $oidcExternalClaimId,
    ) {
        parent::__construct($providerManager);
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
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
                    $signInName = $claims[$this->oidcExternalClaimId];

                    if (empty($signInName)) {
                        throw new CustomUserMessageAuthenticationException('Missing required claim '.$this->oidcExternalClaimId);
                    }

                    $type = UserTypeEnum::OIDC_EXTERNAL;
                    $name = UserService::EXTERNAL_USER_DEFAULT_NAME;
                    $providerId = $this->externalUserService->generatePersonalIdentifierHash($signInName);
                    // Temporary email, will be overridden when the user activates.
                    $email = $providerId.'@ext_not_set';
                    break;
                case self::OIDC_PROVIDER_INTERNAL:
                    $type = UserTypeEnum::OIDC_INTERNAL;
                    $name = $claims[$this->oidcInternalClaimName];
                    $email = $claims[$this->oidcInternalClaimEmail];
                    $providerId = $email;
                    break;
                default:
                    $e = new CustomUserMessageAuthenticationException('Unsupported open_id_connect_provider.');
                    $this->logger->error($e);
                    throw $e;
            }

            // Check if user exists already - if not create a user
            $user = $this->entityManager->getRepository(User::class)->findOneBy(['providerId' => $providerId]);

            if (null === $user) {
                // Create the new user and persist it
                $user = new User();
                $user->setCreatedBy(UserCreatorEnum::OIDC->value);
                $user->setEmail($email);
                $user->setFullName($name);
                $user->setProviderId($providerId);

                $this->entityManager->persist($user);
            }

            $user->setProvider(self::class);
            $user->setUserType($type);

            if (self::OIDC_PROVIDER_INTERNAL === $provider) {
                // Set tenants from claims.
                $oidcGroups = $claims[$this->oidcInternalClaimGroups] ?? [];
                $this->setTenantRoles($user, $oidcGroups);
            }

            $this->entityManager->flush();

            return new SelfValidatingPassport(new UserBadge($user->getUserIdentifier(), $this->getUser(...)));
        } catch (CustomUserMessageAuthenticationException|InvalidProviderException|ItkOpenIdConnectException $exception) {
            $this->logger->error($exception);

            throw new CustomUserMessageAuthenticationException($exception->getMessage());
        }
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return new Response('Auth success', Response::HTTP_OK);
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return new Response('Auth header required', Response::HTTP_UNAUTHORIZED);
    }

    public function getUser(string $identifier): User
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['providerId' => $identifier]);

        if (null === $user) {
            $e = new CustomUserMessageAuthenticationException('User not found.');
            $this->logger->error($e);
            throw $e;
        }

        return $user;
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
            [$tenantKey, $role] = $this->getTenantKeyWithRole($oidcGroup);

            if (!array_key_exists($tenantKey, $tenantKeyRoleMap)) {
                $tenantKeyRoleMap[$tenantKey] = [];
            }

            $tenantKeyRoleMap[$tenantKey][] = $role;
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

        $e = new \InvalidArgumentException('Unknown role for group: '.$oidcGroup);
        $this->logger->error($e);
        throw $e;
    }
}
