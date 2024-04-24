<?php

namespace App\EventSubscriber;

use App\Entity\ScreenUser;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\Cache\CacheInterface;

class ScreenUserRequestSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly Security $security, private readonly CacheInterface $screenStatusCache)
    {
    }

    /**
     * @throws InvalidArgumentException
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        $user = $this->security->getUser();

        if ($user instanceof ScreenUser) {
            $key = $user->getScreen()?->getId()->jsonSerialize() ?? null;

            if ($key === null) {
                return;
            }

            $this->screenStatusCache->delete($key);
            $this->screenStatusCache->get($key, fn() => $this->getCacheData($event));
        }
    }

    private function getCacheData(RequestEvent $event): array
    {
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
