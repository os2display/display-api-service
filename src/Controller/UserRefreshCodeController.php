<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Tenant\UserActivationCode;
use App\Exceptions\CodeGenerationException;
use App\Repository\UserActivationCodeRepository;
use App\Service\UserService;
use App\Utils\ValidationUtils;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[AsController]
class UserRefreshCodeController extends AbstractController
{
    public function __construct(
        private readonly UserActivationCodeRepository $activationCodeRepository,
        private readonly UserService $userService,
        private readonly ValidationUtils $validationUtils,
    ) {}

    public function __invoke(Request $request, string $id): UserActivationCode
    {
        $ulid = $this->validationUtils->validateUlid($id);

        $code = $this->activationCodeRepository->find($ulid);

        if (null === $code) {
            throw new NotFoundHttpException('Activation code not found');
        }

        try {
            return $this->userService->refreshCode($code);
        } catch (CodeGenerationException $e) {
            throw new ConflictHttpException($e->getMessage());
        }
    }
}
