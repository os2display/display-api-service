<?php

declare(strict_types=1);

namespace App\Security\EventListener;

use App\Entity\Interfaces\TenantScopedUserInterface;
use App\Entity\ScreenUser;
use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * class JWTCreatedListener.
 *
 * Set "user" and "tenants" in JWT payload.
 * Set different token ttl for 'User' and 'ScreenUser'.
 */
class JWTCreatedListener
{
    public function __construct(
        private RequestStack $requestStack,
        private int $screenTokenTtl = 86400
    ) {}

    /**
     * @param JWTCreatedEvent $event
     *
     * @return void
     */
    public function onJWTCreated(JWTCreatedEvent $event): void
    {
        $payload = $event->getData();
        $user = $event->getUser();

        if (!$user instanceof TenantScopedUserInterface) {
            return;
        }

        $payload['user'] = $user;
        $payload['tenants'] = $user->getUserRoleTenants()->toArray();

        if ($user instanceof ScreenUser) {
            $now = time();
            $payload['exp'] = $now + $this->screenTokenTtl;
        }

        $event->setData($payload);
    }
}
