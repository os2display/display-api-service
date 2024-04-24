<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Tenant\Feed;
use App\Entity\Tenant\Screen;
use App\Service\FeedService;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Contracts\Cache\CacheInterface;

#[AsController]
final class ScreenStatusController extends AbstractController
{
    public function __construct(
        private readonly CacheInterface $screenStatusCache,
    ) {}

    /**
     * @throws InvalidArgumentException
     */
    public function __invoke(Screen $screen): JsonResponse
    {
        $key = $screen->getId()?->jsonSerialize() ?? null;

        if ($key === null) {
            return new JsonResponse([]);
        }

        return new JsonResponse($this->screenStatusCache->get($key, function (CacheItemInterface $cacheItem) {
            $cacheItem->expiresAfter(1);
            return null;
        }));
    }
}
