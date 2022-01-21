<?php

namespace App\Controller;

use App\Repository\ScreenRepository;
use App\Service\AuthScreenService;
use App\Utils\ValidationUtils;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class AuthScreenBindController extends AbstractController
{
    public function __construct(private AuthScreenService $authScreenService, private ValidationUtils $validationUtils, private ScreenRepository $screenRepository)
    {
    }

    public function __invoke(Request $request, string $id): JsonResponse
    {
        $screenUlid = $this->validationUtils->validateUlid($id);
        $this->screenRepository->find($screenUlid);

        $body = $request->toArray();
        $bindKey = $body['bindKey'];

        if (!isset($bindKey)) {
            throw new \HttpException('Missing bindKey', 400);
        }

        $result = $this->authScreenService->bindScreen($screenUlid, $bindKey);

        if ($result) {
            return new JsonResponse('', 201);
        }

        return new JsonResponse('bindKey not accepted', 400);
    }
}
