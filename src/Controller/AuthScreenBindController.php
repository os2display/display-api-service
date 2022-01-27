<?php

namespace App\Controller;

use App\Entity\Screen;
use App\Repository\ScreenRepository;
use App\Service\AuthScreenService;
use App\Utils\ValidationUtils;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class AuthScreenBindController extends AbstractController
{
    public function __construct(private AuthScreenService $authScreenService, private ValidationUtils $validationUtils, private ScreenRepository $screenRepository)
    {
    }

    public function __invoke(Request $request, string $id): Response
    {
        $screenUlid = $this->validationUtils->validateUlid($id);
        $screen = $this->screenRepository->find($screenUlid);

        $body = $request->toArray();
        $bindKey = $body['bindKey'];

        if (!isset($bindKey)) {
            throw new \HttpException('Missing bindKey', 400);
        }

        $success = $this->authScreenService->bindScreen($screen, $bindKey);

        if ($success) {
            return new Response(null, 201);
        }

        return new JsonResponse('bindKey not accepted', 400);
    }
}
