<?php

declare(strict_types=1);

namespace App\Controller;

use App\Security\ScreenAuthenticator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class AuthScreenController extends AbstractController
{
    public function __construct(
        private readonly ScreenAuthenticator $authScreenService
    ) {}

    public function __invoke(): JsonResponse
    {
        $status = $this->authScreenService->getStatus();
        return new JsonResponse($status);
    }
}
