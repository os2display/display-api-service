<?php

namespace App\Controller;

use App\Service\AuthScreenService;
use App\Utils\ValidationUtils;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class AuthScreenController extends AbstractController
{
    public function __construct(
        private AuthScreenService $authScreenService,
        private ValidationUtils $validationUtils
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $uniqueLoginId = $request->toArray()['uniqueLoginId'];

        $ulid = $this->validationUtils->validateUlid($uniqueLoginId);

        $status = $this->authScreenService->getStatus($ulid);

        return new JsonResponse($status);
    }
}
