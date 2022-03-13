<?php

namespace App\Security;

use Lexik\Bundle\JWTAuthenticationBundle\Services\JWSProvider\JWSProviderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Signature\CreatedJWS;
use Lexik\Bundle\JWTAuthenticationBundle\Signature\LoadedJWS;

/**
 * class JWSProviderDecorator.
 *
 * Decorates Lexik\Bundle\JWTAuthenticationBundle\Services\JWSProvider\LcobucciJWSProvider to set
 * different token ttl for 'User' and 'ScreenUser'
 */
class JWSProviderDecorator implements JWSProviderInterface
{
    // Default ttl for jwt tokens for screens is 1 day
    public const SCREEN_TOKEN_TTL = 2592000;

    public function __construct(private JWSProviderInterface $JWSProvider, private int $screenTokenTtl = 2592000)
    {
    }

    /** {@inheritDoc} */
    public function create(array $payload, array $header = []): CreatedJWS
    {
        if (isset($payload['roles']) && in_array('ROLE_SCREEN', $payload['roles'])) {
            $now = time();
            $payload['exp'] = $now + $this->screenTokenTtl;
        }

        return $this->JWSProvider->create($payload, $header);
    }

    /** {@inheritDoc} */
    public function load($token): LoadedJWS
    {
        return $this->JWSProvider->load($token);
    }
}
