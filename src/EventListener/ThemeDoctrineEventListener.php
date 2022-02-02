<?php

namespace App\EventListener;

use App\Entity\Theme;
use Doctrine\ORM\Event\LifecycleEventArgs;

class ThemeDoctrineEventListener
{
    public function preRemove(Theme $theme, LifecycleEventArgs $event): void
    {
        if (count($theme->getSlides()) > 0) {
            throw new \Exception("Theme cannot be removed since it is bound to one or more slides", 409);
        }
    }
}
