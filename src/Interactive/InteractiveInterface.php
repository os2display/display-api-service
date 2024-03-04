<?php

declare(strict_types=1);

namespace App\Interactive;

use App\Entity\Tenant\Slide;
use App\Exceptions\InteractiveException;
use Symfony\Component\Security\Core\User\UserInterface;

interface InteractiveInterface
{
    public function getConfigOptions(): array;

    /**
     * Perform the given InteractionRequest with the given Slide.
     *
     * @throws InteractiveException
     */
    public function performAction(UserInterface $user, Slide $slide, InteractionRequest $interactionRequest): array;
}
