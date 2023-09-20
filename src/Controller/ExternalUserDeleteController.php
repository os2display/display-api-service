<?php

namespace App\Controller;

use App\Exceptions\ExternalUserCodeException;
use App\Service\ExternalUserService;
use App\Utils\ValidationUtils;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class ExternalUserDeleteController extends AbstractController
{
    public function __construct(
        private readonly ExternalUserService $externalUserService,
        private readonly ValidationUtils $validationUtils,
    ) {}

    /**
     * @throws ExternalUserCodeException
     */
    public function __invoke(Request $request, string $id): JsonResponse
    {
        $ulid = $this->validationUtils->validateUlid($id);

        $this->externalUserService->removeExternalUserFromCurrentTenant($ulid);

        return new JsonResponse(null, 204);
    }
}
