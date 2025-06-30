<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Exceptions\BadRequestException;
use App\Exceptions\NotFoundException;
use App\Service\UserService;
use App\Utils\ValidationUtils;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[AsController]
class UserRemoveFromTenantController extends AbstractController
{
    public function __construct(
        private readonly UserService $externalUserService,
        private readonly ValidationUtils $validationUtils,
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        $ulid = $this->validationUtils->validateUlid($id);

        try {
            $this->externalUserService->removeUserFromCurrentTenant($ulid);
        } catch (BadRequestException $e) {
            throw new BadRequestHttpException($e->getMessage());
        } catch (NotFoundException $e) {
            throw new NotFoundHttpException($e->getMessage());
        }

        return new JsonResponse(null, \Symfony\Component\HttpFoundation\Response::HTTP_NO_CONTENT);
    }
}
