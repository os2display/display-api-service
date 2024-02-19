<?php

namespace App\Interactive;

use App\Entity\Tenant\Slide;

interface InteractiveInterface
{
    public function getConfigOptions(): array;
    public function performAction(Slide $slide, Interaction $interaction): array;
}
