<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\ScreenUser;
use App\Entity\Tenant\Slide;
use App\Entity\User;
use App\Exceptions\NotFoundException;
use App\Service\InteractiveSlideService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
final readonly class InteractiveController
{
    public function __construct(
        private InteractiveSlideService $interactiveSlideService,
        private Security $security,
    ) {}

    public function __invoke(Request $request, Slide $slide): JsonResponse
    {
        $requestBody = $request->toArray();

        $interactionRequest = $this->interactiveSlideService->parseRequestBody($requestBody);

        $user = $this->security->getUser();

        if (!($user instanceof User || $user instanceof ScreenUser)) {
            throw new NotFoundException('User not found');
        }

        return new JsonResponse($this->interactiveSlideService->performAction($user, $slide, $interactionRequest));
    }
}
