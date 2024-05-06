<?php

namespace App\EventSubscriber;

use App\Entity\ScreenUser;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\Cache\CacheInterface;

class ScreenUserRequestSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly CacheInterface $screenStatusCache,
        private readonly EntityManagerInterface $entityManager,
        private readonly bool $trackScreenInfo = false,
        private readonly int $trackScreenInfoUpdateIntervalSeconds = 5 * 60,
    )
    {
    }

    /**
     * @throws InvalidArgumentException
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        $pathInfo = $event->getRequest()->getPathInfo();

        if ($this->trackScreenInfo && preg_match("/^\/v2\/screens\/[A-Za-z0-9]{26}$/i", $pathInfo)) {
            $user = $this->security->getUser();

            if ($user instanceof ScreenUser) {
                $key = $user->getScreen()?->getId()->jsonSerialize() ?? null;

                if ($key === null) {
                    return;
                }

                $this->screenStatusCache->get($key, fn(CacheItemInterface $item) => $this->createCacheEntry($item, $event, $user));
            }
        }
    }

    private function createCacheEntry(CacheItemInterface $item, RequestEvent $event, ScreenUser $screenUser): array
    {
        $item->expiresAfter($this->trackScreenInfoUpdateIntervalSeconds);

        $requestDateTime = new \DateTime();

        $request = $event->getRequest();
        $referer = $request->headers->get('referer') ?? '';
        $url = parse_url($referer);
        $queryString = $url['query'] ?? "";
        $queryArray = [];

        if (!empty($queryString)) {
            parse_str($queryString, $queryArray);
        }

        $releaseVersion = $queryArray['releaseVersion'] ?? null;
        $releaseTimestamp = $queryArray['releaseTimestamp'] ?? null;

        // Update screen user fields.
        $screenUser->setReleaseTimestamp($releaseTimestamp);
        $screenUser->setReleaseVersion($releaseVersion);
        $screenUser->setLatestRequest($requestDateTime);

        $userAgent = $request->headers->get('user-agent') ?? '';
        $ip = $request->getClientIp();

        $clientMeta = [
            'userAgent' => $userAgent,
            'ip' => $ip,
        ];

        $screenUser->setClientMeta($clientMeta);

        $this->entityManager->flush();

        return [
            'latestRequestDateTime' => $requestDateTime->format('c'),
            'releaseVersion' => $releaseVersion,
            'releaseTimestamp' => $releaseTimestamp,
        ];
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }
}
