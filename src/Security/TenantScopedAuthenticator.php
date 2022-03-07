<?php

namespace App\Security;

use App\Entity\Tenant;
use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Authenticator\JWTAuthenticator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

/**
 * Overrides the standard JWTAuthenticator to add support for tenants.
 *
 * @psalm-suppress UnimplementedInterfaceMethod
 */
class TenantScopedAuthenticator extends JWTAuthenticator
{
    public const AUTH_TENANT_ID_HEADER = 'Authorization-Tenant-Key';

    /** @inheritDoc */
    final public function doAuthenticate(Request $request): Passport
    {
        $passport = parent::doAuthenticate($request);
        $user = $passport->getUser();

        if (!$user instanceof User) {
            throw new AuthenticationException('Unknown User class');
        }

        if ($user->getTenants()->isEmpty()) {
            throw new AuthenticationException('User has no tenant access');
        }

        if ($request->headers->has(self::AUTH_TENANT_ID_HEADER)) {
            $requestTenantKey = $request->headers->get(self::AUTH_TENANT_ID_HEADER);

            // Check if the requested tenant is on the users list of tenants and set
            // that tenant as the active tenant.
            $hasTenantAccess = false;
            foreach ($user->getTenants() as $tenant) {
                /** @var Tenant $tenant */
                if ($tenant->getTenantKey() === $requestTenantKey) {
                    $user->setActiveTenant($tenant);
                    $hasTenantAccess = true;
                    break;
                }
            }
            if (!$hasTenantAccess) {
                // If no active tenant is set at this point then the requested tenant is not
                // in the users list of allowed tenants so authentication must fail.
                throw new AuthenticationException('Unknown tenant key or user has no access to tenant: '.$requestTenantKey);
            }
        } else {
            // If no tenant header is given we default to the first tenant in the users list
            $defaultTenant = $user->getTenants()->first();
            $user->setActiveTenant($defaultTenant);
        }

        return $passport;
    }
}
