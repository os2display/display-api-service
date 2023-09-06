<?php

namespace App\Controller;

use App\Exceptions\ExternalUserCodeException;
use App\Exceptions\NoUserException;
use App\Repository\UserRepository;
use App\Service\ExternalUserService;
use App\Utils\ValidationUtils;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class ExternalUserController extends AbstractController
{
    public function __construct(
        private readonly ValidationUtils $validationUtils,
        private readonly UserRepository $userRepository,
        private readonly ExternalUserService $externalUserService,
    ) {}

    /**
     * @throws ExternalUserCodeException
     * @throws NoUserException
     */
    public function __invoke(Request $request, string $id): JsonResponse
    {
        $body = json_decode($request->getContent());
        $activationCode = $body->activationCode;

        $ulid = $this->validationUtils->validateUlid($id);

        $user = $this->userRepository->findOneBy(['id' => $ulid]);

        if ($user === null) {
            throw new NoUserException("User not found", 404);
        }

        $this->externalUserService->activateExternalUser($user, $activationCode);

        return new JsonResponse([], 204);
    }
}
