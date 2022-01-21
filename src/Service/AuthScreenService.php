<?php

namespace App\Service;

use App\Entity\Screen;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Uid\Ulid;
use Symfony\Contracts\Cache\CacheInterface;

class AuthScreenService
{
    public const BIND_KEY_PREFIX = 'BindKey-';

    public function __construct(private CacheInterface $cache, private RequestStack $requestStack)
    {
    }

    public function getResult(): array
    {
        $session = $this->requestStack->getSession();
        $authScreenNonce = $session->get('authScreenNonce');

        if ($authScreenNonce == null) {
            $authScreenNonce = Ulid::generate();
            $session->set('authScreenNonce', $authScreenNonce);
        }

        $cacheItem = $this->cache->getItem($authScreenNonce);

        if ($cacheItem->isHit()) {
            // Entry exists. Return the item.
            $result = $cacheItem->get();
        } else {
            // Get unique bind key.
            do {
                $bindKey = AuthScreenService::generateBindKey();
                $bindKeyCacheItem = $this->cache->getItem(AuthScreenService::BIND_KEY_PREFIX.$bindKey);
            } while ($bindKeyCacheItem->isHit());

            // TODO: Make expire configurable.
            // $bindKeyCacheItem->expiresAfter(3600);

            $bindKeyCacheItem->set($authScreenNonce);
            $this->cache->save($bindKeyCacheItem);

            // Entry does not exist. Create entry with bindKey and return in response, remember cache expire.
            $result = [
                'bindKey' => $bindKey,
            ];

            $cacheItem->set($result);

            // TODO: Make expire configurable.
            // $cacheItem->expiresAfter(3600);

            $this->cache->save($cacheItem);
        }

        return $result;
    }

    public function bindScreen(string $screenUlid, string $bindKey): bool
    {
        // TODO: Check that screen has not been bound before.
        // TODO: Add option to eject bound screen token.
        $session = $this->requestStack->getSession();
        $authScreenNonce = $session->get('authScreenNonce');

        if ($authScreenNonce == null) {
            $authScreenNonce = Ulid::generate();
            $session->set('authScreenNonce', $authScreenNonce);
        }

        $cacheItem = $this->cache->getItem($authScreenNonce);
        if ($cacheItem->isHit()) {
            // Entry exists. Return the item.
            $entry = $cacheItem->get();

            if (isset($entry['bindKey']) && $entry['bindKey'] == $bindKey) {
                $cacheItem->set([
                    'token' => 'TODO',
                    'screenId' => $screenUlid,
                ]);

                $this->cache->save($cacheItem);
                return true;
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
