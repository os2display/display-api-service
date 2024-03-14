<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Tenant\UserActivationCode;
use App\Exceptions\CodeGenerationException;
use App\Exceptions\NotFoundException;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[AsController]
class UserRefreshCodeController extends AbstractController
{
    public function __construct(
        private readonly UserService $userService,
    ) {}

    public function __invoke(Request $request): UserActivationCode
    {
        $body = $request->toArray();

        $activationCode = $body['activationCode'] ?? null;

        if (null === $activationCode) {
            throw new BadRequestHttpException('Missing activation code');
        }

        try {
            return $this->userService->refreshCode($activationCode);
        } catch (NotFoundException $e) {
            throw new NotFoundHttpException($e->getMessage());
        } catch (CodeGenerationException $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }
}
