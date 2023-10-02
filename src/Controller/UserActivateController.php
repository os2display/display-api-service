<?php

namespace App\Controller;

use App\Exceptions\UserException;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\HttpException;

#[AsController]
class UserActivateController extends AbstractController
{
    public function __construct(
        private readonly UserService $externalUserService,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $body = json_decode($request->getContent());
        $activationCode = $body->activationCode;

        try {
            $this->externalUserService->activateExternalUser($activationCode);
        } catch (UserException $e) {
            throw new HttpException($e->getCode(), $e->getMessage());
        }

        return new JsonResponse(null, 204);
    }
}
