<?php

namespace App\Security;

use App\Entity\Interfaces\TenantScopedUserInterface;
use App\Entity\Tenant;
use App\Entity\User;
use App\Enum\UserTypeEnum;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Authenticator\JWTAuthenticator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

/**
 * Overrides the standard JWTAuthenticator to add support for external users.
 *
 * @psalm-suppress UnimplementedInterfaceMethod
 */
class ExternalUserAuthenticator extends JWTAuthenticator
{
    /** {@inheritDoc} */
    final public function doAuthenticate(Request $request): Passport
    {
        $passport = parent::doAuthenticate($request);
        $user = $passport->getUser();

        if (!$user instanceof User) {
            throw new AuthenticationException('UserInterface not of User type');
        }

        if ($user->getUserType() !== UserTypeEnum::OIDC_EXTERNAL) {
            throw new AuthenticationException('User not of external type');
        }

        return $passport;
    }
}
