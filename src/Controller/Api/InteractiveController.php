<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\ScreenUser;
use App\Entity\Tenant\Slide;
use App\Exceptions\BadRequestException;
use App\Exceptions\ConflictException;
use App\Exceptions\NotAcceptableException;
use App\Exceptions\TooManyRequestsException;
use App\Service\InteractiveSlideService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

#[AsController]
final readonly class InteractiveController
{
    public function __construct(
        private InteractiveSlideService $interactiveSlideService,
        private Security $security,
    ) {}

    /**
     * @throws ConflictException
     * @throws BadRequestException
     * @throws NotAcceptableException
     * @throws TooManyRequestsException
     */
    public function __invoke(Request $request, Slide $slide): JsonResponse
    {
        $user = $this->security->getUser();

        if (!$user instanceof ScreenUser) {
            throw new AccessDeniedHttpException('Only screen user can perform action.');
        }

        $tenant = $user->getActiveTenant();

        $requestBody = $request->toArray();

        $interactionRequest = $this->interactiveSlideService->parseRequestBody($requestBody);

        $actionResult = $this->interactiveSlideService->performAction($tenant, $slide, $interactionRequest);

        return new JsonResponse($actionResult);
    }
}
