<?php

declare(strict_types=1);

namespace App\InteractiveSlide;

use App\Entity\Tenant;
use App\Entity\Tenant\Slide;
use App\Exceptions\BadRequestException;
use App\Exceptions\ConflictException;
use App\Exceptions\NotAcceptableException;
use App\Exceptions\TooManyRequestsException;

interface InteractiveSlideInterface
{
    public function getConfigOptions(): array;

    /**
     * Perform the given InteractionRequest with the given Slide.
     *
     * @throws ConflictException
     * @throws BadRequestException
     * @throws NotAcceptableException
     * @throws TooManyRequestsException
     */
    public function performAction(Tenant $tenant, Slide $slide, InteractionSlideRequest $interactionRequest): array;
}
