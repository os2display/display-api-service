<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Repository\PlaylistSlideRepository;
use App\Utils\ValidationUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class PlaylistSlideDeleteController extends AbstractTenantAwareController
{
    public function __construct(
        private readonly PlaylistSlideRepository $playlistSlideRepository,
        private readonly ValidationUtils $validationUtils,
    ) {}

    public function __invoke(string $id, string $slideId): JsonResponse
    {
        $ulid = $this->validationUtils->validateUlid($id);
        $slideUlid = $this->validationUtils->validateUlid($slideId);

        $this->playlistSlideRepository->deleteRelations($ulid, $slideUlid, $this->getActiveTenant());

        return new JsonResponse(null, \Symfony\Component\HttpFoundation\Response::HTTP_NO_CONTENT);
    }
}
