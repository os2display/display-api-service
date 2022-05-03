<?php

namespace App\Controller;

use App\Repository\PlaylistSlideRepository;
use App\Utils\ValidationUtils;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class PlaylistSlideDeleteController extends AbstractController
{
    public function __construct(
        private PlaylistSlideRepository $playlistSlideRepository,
        private ValidationUtils $validationUtils
    ) {
    }

    public function __invoke(string $id, string $slideId): JsonResponse
    {
        $ulid = $this->validationUtils->validateUlid($id);
        $slideUlid = $this->validationUtils->validateUlid($slideId);

        $this->playlistSlideRepository->deleteRelations($ulid, $slideUlid);

        return new JsonResponse(null, 204);
    }
}
