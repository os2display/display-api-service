<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\ScreenUser;
use App\Entity\Tenant\Slide;
use App\Entity\User;
use App\Exceptions\InteractiveSlideException;
use App\Service\InteractiveSlideService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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

        try {
            $interactionRequest = $this->interactiveSlideService->parseRequestBody($requestBody);
        } catch (InteractiveSlideException $e) {
            throw new HttpException($e->getCode(), $e->getMessage());
        }

        $user = $this->security->getUser();

        if (!($user instanceof User || $user instanceof ScreenUser)) {
            throw new NotFoundHttpException('User not found');
        }

        try {
            $actionResult = $this->interactiveSlideService->performAction($user, $slide, $interactionRequest);
        } catch (InteractiveSlideException $e) {
            throw new HttpException($e->getCode(), $e->getMessage());
        }

        return new JsonResponse($actionResult);
    }
}
