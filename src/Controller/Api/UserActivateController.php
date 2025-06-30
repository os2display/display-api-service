<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Exceptions\BadRequestException;
use App\Exceptions\ConflictException;
use App\Exceptions\NotFoundException;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[AsController]
class UserActivateController extends AbstractController
{
    public function __construct(
        private readonly UserService $externalUserService,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $body = $request->toArray();
        $activationCode = $body['activationCode'] ?? null;

        if (null === $activationCode) {
            throw new BadRequestHttpException('Missing activation code');
        }

        try {
            $this->externalUserService->activateExternalUser($activationCode);
        } catch (BadRequestException $e) {
            throw new BadRequestHttpException($e->getMessage());
        } catch (NotFoundException $e) {
            throw new NotFoundHttpException($e->getMessage());
        } catch (ConflictException $e) {
            throw new ConflictHttpException($e->getMessage());
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
