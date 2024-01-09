<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Tenant\Slide;
use App\Service\InteractiveSlideService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
final readonly class InteractiveSlideActionController
{
    public function __construct(
        private InteractiveSlideService $interactiveSlideService
    ) {}

    public function __invoke(Request $request, Slide $slide): JsonResponse
    {
        $requestBody = $request->toArray();

        return new JsonResponse($this->interactiveSlideService->performAction($slide, $requestBody));
    }
}
