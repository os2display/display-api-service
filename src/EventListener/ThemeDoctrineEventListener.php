<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\Tenant\Theme;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class ThemeDoctrineEventListener
{
    public function preRemove(Theme $theme, PreRemoveEventArgs $args): void
    {
        if (count($theme->getSlides()) > 0) {
            throw new ConflictHttpException('Theme cannot be removed since it is bound to one or more slides');
        }
    }
}
