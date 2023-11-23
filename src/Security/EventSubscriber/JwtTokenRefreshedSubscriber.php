<?php

declare(strict_types=1);

namespace App\Security\EventSubscriber;

use App\Entity\ScreenUser;
use Gesdinet\JWTRefreshTokenBundle\Exception\InvalidRefreshTokenException;
use Gesdinet\JWTRefreshTokenBundle\Exception\UnknownRefreshTokenException;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * class JwtTokenRefreshedSubscriber.
 *
 * gesdinet/jwt-refresh-token-bundle only allows for setting one TTL value
 * for the refresh token. This subscriber handles setting a custom TTL value
 * for screens (.env:JWT_SCREEN_REFRESH_TOKEN_TTL).
 */
class JwtTokenRefreshedSubscriber implements EventSubscriberInterface
{
    private int $jwtRefreshTokenTtl;
    private int $jwtScreenRefreshTokenTtl;
    private RefreshTokenManagerInterface $refreshTokenManager;

    /**
     * JwtTokenRefreshedSubscriber constructor.
     *
     * @param int $jwtRefreshTokenTtl
     * @param int $jwtScreenRefreshTokenTtl
     * @param RefreshTokenManagerInterface $refreshTokenManager
     */
    public function __construct(int $jwtRefreshTokenTtl, int $jwtScreenRefreshTokenTtl, RefreshTokenManagerInterface $refreshTokenManager)
    {
        $this->jwtRefreshTokenTtl = $jwtRefreshTokenTtl;
        $this->jwtScreenRefreshTokenTtl = $jwtScreenRefreshTokenTtl;
        $this->refreshTokenManager = $refreshTokenManager;
    }

    public function onLexikJwtAuthenticationOnAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
        if ($event->getUser() instanceof ScreenUser) {
            $data = $event->getData();

            if (array_key_exists('refresh_token', $data) && empty($data['refresh_token'])) {
                throw new UnknownRefreshTokenException('No refresh token found when renewing');
            }
            if (array_key_exists('refresh_token_expiration', $data) && empty($data['refresh_token_expiration'])) {
                throw new UnknownRefreshTokenException('No refresh token expiration found when renewing');
            }

            $refreshTokenString = $data['refresh_token'];
            $refreshToken = $this->refreshTokenManager->get($refreshTokenString);

            if (null === $refreshToken) {
                throw new UnknownRefreshTokenException('Could not find refresh token');
            }

            // gesdinet/jwt-refresh-token-bundle only allows for setting one TTL value
            // for the refresh token. The token set has the ttl value for 'users'.
            // For screens, we need to modify the set TTL with the difference between
            // the TTL for Users ($jwtRefreshTokenTtl) and ScreenUsers ($jwtScreenRefreshTokenTtl)
            $ttlDiff = $this->jwtScreenRefreshTokenTtl - $this->jwtRefreshTokenTtl;
            $modifier = $ttlDiff > 0 ? '+' : '-';

            $valid = $refreshToken->getValid();

            if (null === $valid) {
                throw new InvalidRefreshTokenException('Refresh token valid is null');
            }

            // Use clone because The 'setValid()' function needs a new datetime object
            // to trigger a doctrine update. Just modifying the object returned by getValid()
            // is not enough to mark the refreshToken entity as dirty.
            $validClone = clone $valid;

            $validDateTimeImmutable = \DateTimeImmutable::createFromInterface($validClone);
            $modifiedValidDateTimeImmutable = $validDateTimeImmutable->modify(sprintf('%s%d seconds', $modifier, abs($ttlDiff)));

            if (!$modifiedValidDateTimeImmutable) {
                throw new InvalidRefreshTokenException('Could not modify valid');
            }

            $refreshToken->setValid($modifiedValidDateTimeImmutable);

            $this->refreshTokenManager->save($refreshToken);

            $valid = $refreshToken->getValid();

            if (null === $valid) {
                throw new InvalidRefreshTokenException('Refresh token valid is null');
            }

            $data['refresh_token_expiration'] = $valid->getTimestamp();
            $event->setData($data);
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'lexik_jwt_authentication.on_authentication_success' => [
                ['onLexikJwtAuthenticationOnAuthenticationSuccess', -100],
            ],
        ];
    }
}
