<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Interfaces\TenantScopedUserInterface;
use App\Entity\Tenant;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

abstract class AbstractTenantAwareController extends AbstractController
{
    protected function getActiveTenant(): Tenant
    {
        $user = $this->getUser();

        if (null === $user) {
            throw new \RuntimeException('Authenticated user cannot be null');
        }

        if (!$user instanceof TenantScopedUserInterface) {
            throw new \RuntimeException('Authenticated user must be instance of TenantScopedUserInterface');
        }

        return $user->getActiveTenant();
    }
}
