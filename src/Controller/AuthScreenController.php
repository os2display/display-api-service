<?php

namespace App\Controller;

use App\Service\AuthScreenService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class AuthScreenController extends AbstractController
{
    public function __construct(
        private AuthScreenService $authScreenService
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $status = $this->authScreenService->getStatus();

        return new JsonResponse($status);
    }
}
