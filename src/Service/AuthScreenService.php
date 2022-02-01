<?php

namespace App\Service;

use App\Entity\Screen;
use App\Entity\ScreenUser;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Uid\Ulid;
use Symfony\Contracts\Cache\CacheInterface;

class AuthScreenService
{
    public const BIND_KEY_PREFIX = 'BindKey-';

    public function __construct(
        private CacheInterface $authscreenCache,
        private JWTTokenManagerInterface $JWTManager,
        private EntityManagerInterface $entityManager
    ) {
    }

    public function getStatus(Ulid $uniqueLoginId): array
    {
        $cacheKey = $uniqueLoginId->toRfc4122();
        $cacheItem = $this->authscreenCache->getItem($cacheKey);

        if ($cacheItem->isHit()) {
            // Entry exists. Return the item.
            $result = $cacheItem->get();

            if (isset($result['token']) && isset($result['screenId'])) {
                // Remove cache entry.
                $this->authscreenCache->delete($cacheKey);

                $result['status'] = 'ready';
            }
        } else {
            // Get unique bind key.
            do {
                $bindKey = AuthScreenService::generateBindKey();
                $bindKeyCacheItem = $this->authscreenCache->getItem(AuthScreenService::BIND_KEY_PREFIX.$bindKey);
            } while ($bindKeyCacheItem->isHit());

            $bindKeyCacheItem->set($cacheKey);
            $this->authscreenCache->save($bindKeyCacheItem);

            // Entry does not exist. Create entry with bindKey and return in response, remember cache expire.
            $result = [
                'bindKey' => $bindKey,
                'status' => 'awaitingBindKey',
            ];

            $cacheItem->set($result);

            $this->authscreenCache->save($cacheItem);
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
        $bindKeyCacheItem = $this->authscreenCache->getItem(AuthScreenService::BIND_KEY_PREFIX.$bindKey);

        if ($bindKeyCacheItem->isHit()) {
            $uniqueLoginId = $bindKeyCacheItem->get();

            $cacheItem = $this->authscreenCache->getItem($uniqueLoginId);
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

                    $this->authscreenCache->save($cacheItem);

                    // Remove bindKey entry.
                    $this->authscreenCache->delete(AuthScreenService::BIND_KEY_PREFIX.$bindKey);
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
        if (null != $screen->getScreenUser()) {
            $this->entityManager->remove($screen->getScreenUser());
            $this->entityManager->flush();
        } else {
            throw new \Exception('Screen user does not exist', 404);
        }
    }

    private function generateBindKey(): string
    {
        $length = 8;
        $chars = '123456789ABCDEFGHIJKLMNPQRSTUVWXYZ';
        $charsLength = strlen($chars);
        $bindKey = '';

        for ($i = 0; $i < $length; ++$i) {
            $bindKey .= $chars[rand(0, $charsLength - 1)];
        }

        return $bindKey;
    }
}
