<?php

namespace App\Controller;

use App\Exceptions\ExternalUserCodeException;
use App\Service\ExternalUserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\HttpException;

#[AsController]
class ExternalUserActivateController extends AbstractController
{
    public function __construct(
        private readonly ExternalUserService $externalUserService,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $body = json_decode($request->getContent());
        $activationCode = $body->activationCode;

        try {
            $this->externalUserService->activateExternalUser($activationCode);
        } catch (ExternalUserCodeException $e) {
            throw new HttpException($e->getCode(), $e->getMessage());
        }

        return new JsonResponse(null, 204);
    }
}
