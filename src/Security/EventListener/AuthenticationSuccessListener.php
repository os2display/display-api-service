<?php

declare(strict_types=1);

namespace App\Security\EventListener;

use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;

/**
 * class AuthenticationSuccessListener.
 *
 * Set "user" and "tenants" in the response body on get token endpoint
 */
class AuthenticationSuccessListener
{
    public function onAuthenticationSuccessResponse(AuthenticationSuccessEvent $event): void
    {
        $data = $event->getData();
        $user = $event->getUser();

        if (!$user instanceof User) {
            return;
        }

        $data['user'] = $user;
        $data['tenants'] = $user->getUserRoleTenants()->toArray();

        $event->setData($data);
    }
}
