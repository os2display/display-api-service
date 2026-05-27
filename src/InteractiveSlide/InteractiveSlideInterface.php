<?php

declare(strict_types=1);

namespace App\InteractiveSlide;

use App\Entity\Tenant;
use App\Entity\Tenant\Slide;

interface InteractiveSlideInterface
{
    public function getConfigOptions(): array;

    /**
     * Perform the given InteractionRequest with the given Slide.
     *
     * @throws \Throwable
     */
    public function performAction(Tenant $tenant, Slide $slide, InteractionSlideRequest $interactionRequest): array;
}
