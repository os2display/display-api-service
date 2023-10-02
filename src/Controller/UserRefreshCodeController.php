<?php

namespace App\Controller;

use App\Exceptions\CodeGenerationException;
use App\Repository\UserActivationCodeRepository;
use App\Service\UserService;
use App\Utils\ValidationUtils;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class UserRefreshCodeController extends AbstractController
{
    public function __construct(
        private readonly UserActivationCodeRepository $activationCodeRepository,
        private readonly UserService $userService,
        private readonly ValidationUtils $validationUtils,
    ) {}

    /**
     * @throws CodeGenerationException
     * @throws \Exception
     */
    public function __invoke(Request $request, string $id)
    {
        $ulid = $this->validationUtils->validateUlid($id);

        $code = $this->activationCodeRepository->find($ulid);

        if (null === $code) {
            throw new \HttpException('Activation code not found', 404);
        }

        return $this->userService->refreshCode($code);
    }
}
