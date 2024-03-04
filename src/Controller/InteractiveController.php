<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Tenant\Slide;
use App\Service\InteractiveService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
final readonly class InteractiveController
{
    public function __construct(
        private InteractiveService $interactiveSlideService,
        private Security $security,
    ) {}

    public function __invoke(Request $request, Slide $slide): JsonResponse
    {
        $requestBody = $request->toArray();

        $interaction = $this->interactiveSlideService->parseRequestBody($requestBody);

        $user = $this->security->getUser();

        return new JsonResponse($this->interactiveSlideService->performAction($user, $slide, $interaction));
    }
}
