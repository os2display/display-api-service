<?php

namespace App\Service;

use App\Entity\Tenant\Slide;
use App\Interactive\InteractiveInterface;

class InteractiveService
{
    public function __construct(
        /** @var array<InteractiveInterface> $interactives */
        private readonly iterable $interactives,
    ) {}

    public function performAction(Slide $slide, array $requestBody): array
    {
        return [];
    }

    /**
     * Get configurable interactive.
     */
    public function getConfigurables(): array
    {
        $result = [];

        foreach ($this->interactives as $interactive) {
            $result[$interactive::class] = $interactive->getConfigOptions();
        }

        return $result;
    }
}
