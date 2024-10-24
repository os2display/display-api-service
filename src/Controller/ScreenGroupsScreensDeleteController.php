<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\ScreenGroupRepository;
use App\Utils\ValidationUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class ScreenGroupsScreensDeleteController extends AbstractTenantAwareController
{
    public function __construct(
        private readonly ScreenGroupRepository $screenGroupRepository,
        private readonly ValidationUtils $validationUtils,
    ) {}

    public function __invoke(string $id, string $screenGroupId): JsonResponse
    {
        $ulid = $this->validationUtils->validateUlid($id);
        $screenGroupUlid = $this->validationUtils->validateUlid($screenGroupId);

        $this->screenGroupRepository->deleteRelations($ulid, $screenGroupUlid, $this->getActiveTenant());

        return new JsonResponse(null, \Symfony\Component\HttpFoundation\Response::HTTP_NO_CONTENT);
    }
}
