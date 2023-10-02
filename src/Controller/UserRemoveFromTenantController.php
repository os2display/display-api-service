<?php

namespace App\Controller;

use App\Exceptions\UserException;
use App\Service\UserService;
use App\Utils\ValidationUtils;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\HttpException;

#[AsController]
class UserRemoveFromTenantController extends AbstractController
{
    public function __construct(
        private readonly UserService $externalUserService,
        private readonly ValidationUtils $validationUtils,
    ) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        $ulid = $this->validationUtils->validateUlid($id);

        try {
            $this->externalUserService->removeUserFromCurrentTenant($ulid);
        } catch (UserException $e) {
            throw new HttpException($e->getCode(), $e->getMessage());
        }

        return new JsonResponse(null, 204);
    }
}
