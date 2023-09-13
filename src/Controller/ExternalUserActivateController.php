<?php

namespace App\Controller;

use App\Exceptions\ExternalUserCodeException;
use App\Exceptions\NoUserException;
use App\Repository\UserRepository;
use App\Service\ExternalUserService;
use App\Utils\ValidationUtils;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class ExternalUserActivateController extends AbstractController
{
    public function __construct(
        private readonly ExternalUserService $externalUserService,
    ) {}

    /**
     * @throws ExternalUserCodeException
     */
    public function __invoke(Request $request): JsonResponse
    {
        $body = json_decode($request->getContent());
        $activationCode = $body->activationCode;

        $this->externalUserService->activateExternalUser($activationCode);

        return new JsonResponse(null, 204);
    }
}
