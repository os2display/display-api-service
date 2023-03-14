<?php

namespace App\Security\EventListener;

use App\Entity\ScreenUser;
use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Symfony\Component\HttpFoundation\RequestStack;

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

        if (!$user instanceof User) {
            return;
        }

        $payload['user'] = $user;
        $payload['tenants'] = $user->getUserRoleTenants()->toArray();

        if (isset($payload['roles']) && in_array(ScreenUser::ROLE_SCREEN, $payload['roles'])) {
            $now = time();
            $payload['exp'] = $now + $this->screenTokenTtl;
        }

        $event->setData($payload);
    }
}
