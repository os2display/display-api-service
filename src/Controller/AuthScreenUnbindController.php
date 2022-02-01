<?php

namespace App\Controller;

use App\Repository\ScreenRepository;
use App\Service\AuthScreenService;
use App\Utils\ValidationUtils;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class AuthScreenUnbindController extends AbstractController
{
    public function __construct(private AuthScreenService $authScreenService, private ValidationUtils $validationUtils, private ScreenRepository $screenRepository)
    {
    }

    public function __invoke(Request $request, string $id): Response
    {
        $screenUlid = $this->validationUtils->validateUlid($id);
        $screen = $this->screenRepository->find($screenUlid);

        $this->authScreenService->unbindScreen($screen);

        return new Response('', 201);
    }
}