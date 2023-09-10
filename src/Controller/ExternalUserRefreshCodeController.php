<?php

namespace App\Controller;

use App\Exceptions\CodeGenerationException;
use App\Exceptions\ExternalUserCodeException;
use App\Exceptions\NoUserException;
use App\Repository\ExternalUserActivationCodeRepository;
use App\Repository\UserRepository;
use App\Service\ExternalUserService;
use App\Utils\ValidationUtils;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class ExternalUserRefreshCodeController extends AbstractController
{
    public function __construct(
        private readonly ExternalUserActivationCodeRepository $activationCodeRepository,
        private readonly ExternalUserService $externalUserService,
        private readonly ValidationUtils $validationUtils,
    ) {}

    /**
     * @throws CodeGenerationException
     * @throws \Exception
     */
    public function __invoke(Request $request, string $id): JsonResponse
    {
        $ulid = $this->validationUtils->validateUlid($id);

        $code = $this->activationCodeRepository->find($ulid);

        if ($code === null) {
            throw new \Exception("Not found", 404);
        }

        $this->externalUserService->refreshCode($code);

        return new JsonResponse([], 204);
    }
}
