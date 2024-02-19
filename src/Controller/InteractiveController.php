<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Tenant\Slide;
use App\Service\InteractiveService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
final readonly class InteractiveController
{
    public function __construct(
        private InteractiveService $interactiveSlideService
    ) {}

    public function __invoke(Request $request, Slide $slide): JsonResponse
    {
        $requestBody = $request->toArray();

        $interaction = $this->interactiveSlideService->parseRequestBody($requestBody);

        return new JsonResponse($this->interactiveSlideService->performAction($slide, $interaction));
    }
}
