<?php

namespace App\Service;

use App\Entity\Screen;
use App\Entity\ScreenUser;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Uid\Ulid;
use Symfony\Contracts\Cache\CacheInterface;

class AuthScreenService
{
    public const BIND_KEY_PREFIX = 'BindKey-';

    public function __construct(
        private CacheInterface $cache,
        private JWTTokenManagerInterface $JWTManager,
        private EntityManagerInterface $entityManager
    ){}

    public function getStatus(Ulid $uniqueLoginId): array
    {
        $cacheKey = $uniqueLoginId->toRfc4122();
        $cacheItem = $this->cache->getItem($cacheKey);

        if ($cacheItem->isHit()) {
            // Entry exists. Return the item.
            $result = $cacheItem->get();

            if (isset($result['token']) && isset($result['screenId'])) {
                // Remove cache entry.
                $this->cache->delete($cacheKey);

                $result['status'] = 'ready';
            }
        } else {
            // Get unique bind key.
            do {
                $bindKey = AuthScreenService::generateBindKey();
                $bindKeyCacheItem = $this->cache->getItem(AuthScreenService::BIND_KEY_PREFIX.$bindKey);
            } while ($bindKeyCacheItem->isHit());

            $bindKeyCacheItem->set($cacheKey);
            $this->cache->save($bindKeyCacheItem);

            // Entry does not exist. Create entry with bindKey and return in response, remember cache expire.
            $result = [
                'bindKey' => $bindKey,
                'status' => 'awaitingBindKey',
            ];

            $cacheItem->set($result);

            $this->cache->save($cacheItem);
        }

        return $result;
    }

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Exception
     */
    public function bindScreen(Screen $screen, string $bindKey): bool
    {
        // TODO: Check that screen has not been bound before.
        // TODO: Add option to eject bound screen token.

        // Get $authScreenNonce from bindKey.
        $bindKeyCacheItem = $this->cache->getItem(AuthScreenService::BIND_KEY_PREFIX.$bindKey);

        if ($bindKeyCacheItem->isHit()) {
            $uniqueLoginId = $bindKeyCacheItem->get();

            $cacheItem = $this->cache->getItem($uniqueLoginId);
            if ($cacheItem->isHit()) {
                // Entry exists. Return the item.
                $entry = $cacheItem->get();

                if (isset($entry['bindKey']) && $entry['bindKey'] == $bindKey) {
                    $screenUser = $screen->getScreenUser();

                    if ($screenUser) {
                        throw new \Exception('Screen already bound');
                    }

                    $screenUser = new ScreenUser();
                    $screenUser->setUsername($screen->getId());
                    $screenUser->setRoles(['ROLE_SCREEN']);
                    $screenUser->setScreen($screen);

                    $this->entityManager->persist($screenUser);
                    $this->entityManager->flush();

                    $cacheItem->set([
                        'token' => $this->JWTManager->create($screenUser),
                        'screenId' => $screen->getId(),
                    ]);

                    $this->cache->save($cacheItem);

                    // Remove bindKey entry.
                    $this->cache->delete(AuthScreenService::BIND_KEY_PREFIX.$bindKey);

                    return true;
                }
            }
        }

        return false;
    }

    private function generateBindKey(): string
    {
        $length = 8;
        $chars = '123456789ABCDEFGHIJKLMNPQRSTUVWXYZ';
        $charsLength = strlen($chars);
        $bindKey = '';

        for ($i = 0; $i < $length; $i++) {
            $bindKey .= $chars[rand(0, $charsLength - 1)];
        }

        return $bindKey;
    }
}
