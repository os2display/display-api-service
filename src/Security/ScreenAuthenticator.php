<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\ScreenUser;
use App\Entity\Tenant\Screen;
use App\Exceptions\EntityException;
use Doctrine\ORM\EntityManagerInterface;
use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGeneratorInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Uid\Ulid;

class ScreenAuthenticator
{
    final public const BIND_KEY_PREFIX = 'BindKey-';
    final public const AUTH_SCREEN_LOGIN_KEY = 'authScreenLoginKey';

    public function __construct(
        private readonly int $jwtScreenRefreshTokenTtl,
        private readonly CacheItemPoolInterface $authScreenCache,
        private readonly JWTTokenManagerInterface $JWTManager,
        private readonly EntityManagerInterface $entityManager,
        private readonly RequestStack $requestStack,
        private readonly RefreshTokenGeneratorInterface $refreshTokenGenerator,
        private readonly RefreshTokenManagerInterface $refreshTokenManager
    ) {}

    /**
     * @throws InvalidArgumentException
     */
    public function getStatus(): array
    {
        $session = $this->requestStack->getSession();
        $cacheKey = $session->get(self::AUTH_SCREEN_LOGIN_KEY);

        // Make sure we have authScreenLoginKey in session.
        if (!$cacheKey) {
            $cacheKey = Ulid::generate();
            $session->set(self::AUTH_SCREEN_LOGIN_KEY, $cacheKey);
        }

        $cacheItem = $this->authScreenCache->getItem($cacheKey);

        if ($cacheItem->isHit()) {
            // Entry exists. Return the item.
            $result = $cacheItem->get();

            if (isset($result['token']) && isset($result['screenId'])) {
                // Remove cache entry.
                $this->authScreenCache->deleteItem($cacheKey);

                $result['status'] = 'ready';

                // Remove session key.
                $session->remove(self::AUTH_SCREEN_LOGIN_KEY);
            }
        } else {
            // Get unique bind key.
            do {
                $bindKey = self::generateBindKey();
                $bindKeyCacheItem = $this->authScreenCache->getItem(self::BIND_KEY_PREFIX.$bindKey);
            } while ($bindKeyCacheItem->isHit());

            $bindKeyCacheItem->set($cacheKey);
            $this->authScreenCache->save($bindKeyCacheItem);

            // Entry does not exist. Create entry with bindKey and return in response, remember cache expire.
            $result = [
                'bindKey' => $bindKey,
                'status' => 'awaitingBindKey',
            ];

            $cacheItem->set($result);

            $this->authScreenCache->save($cacheItem);
        }

        return $result;
    }

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Exception
     */
    public function bindScreen(Screen $screen, string $bindKey): void
    {
        // TODO: Check that screen has not been bound before.
        // TODO: Add option to eject bound screen token.

        // Get $authScreenNonce from bindKey.
        $bindKeyCacheItem = $this->authScreenCache->getItem(ScreenAuthenticator::BIND_KEY_PREFIX.$bindKey);

        if ($bindKeyCacheItem->isHit()) {
            $uniqueLoginId = $bindKeyCacheItem->get();

            $cacheItem = $this->authScreenCache->getItem($uniqueLoginId);
            if ($cacheItem->isHit()) {
                // Entry exists. Return the item.
                $entry = $cacheItem->get();

                if (isset($entry['bindKey']) && $entry['bindKey'] == $bindKey) {
                    $screenUser = $screen->getScreenUser();

                    if ($screenUser) {
                        throw new \Exception('Screen already bound');
                    }

                    $screenId = $screen->getId();

                    if (null === $screenId) {
                        throw new EntityException('Screen id is null');
                    }

                    $screenUser = new ScreenUser();
                    $screenUser->setUsername($screenId->jsonSerialize());
                    $screenUser->setScreen($screen);
                    $screenUser->setTenant($screen->getTenant());

                    $this->entityManager->persist($screenUser);
                    $this->entityManager->flush();

                    $refreshToken = $this->refreshTokenGenerator->createForUserWithTtl($screenUser, $this->jwtScreenRefreshTokenTtl);
                    $this->refreshTokenManager->save($refreshToken);
                    $refreshTokenString = $refreshToken->getRefreshToken();

                    $refreshTokenValid = $refreshToken->getValid();

                    if (null === $refreshTokenValid) {
                        throw new EntityException('Refresh token valid is null');
                    }

                    $cacheItem->set([
                        'token' => $this->JWTManager->create($screenUser),
                        'refresh_token' => $refreshTokenString,
                        'refresh_token_expiration' => $refreshTokenValid->getTimestamp(),
                        'refresh_token_ttl' => $this->jwtScreenRefreshTokenTtl,
                        'screenId' => $screen->getId(),
                        'tenantKey' => $screenUser->getTenant()->getTenantKey(),
                        'tenantId' => $screenUser->getTenant()->getId(),
                    ]);

                    $this->authScreenCache->save($cacheItem);

                    // Remove bindKey entry.
                    $this->authScreenCache->deleteItem(ScreenAuthenticator::BIND_KEY_PREFIX.$bindKey);
                }
            } else {
                throw new \Exception('Not found', 404);
            }
        } else {
            throw new \Exception('Not found', 404);
        }
    }

    /**
     * @throws \Exception
     */
    public function unbindScreen(Screen $screen): void
    {
        $screenUser = $screen->getScreenUser();
        if (null !== $screenUser) {
            $this->entityManager->remove($screenUser);
            $this->entityManager->flush();
        } else {
            throw new \Exception('Screen user does not exist', 404);
        }
    }

    private function generateBindKey(): string
    {
        $length = 8;
        $chars = '0123456789';
        $charsLength = strlen($chars);
        $bindKey = '';

        for ($i = 0; $i < $length; ++$i) {
            $bindKey .= $chars[random_int(0, $charsLength - 1)];
        }

        return $bindKey;
    }
}
