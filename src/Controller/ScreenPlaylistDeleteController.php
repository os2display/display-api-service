<?php

namespace App\Controller;

use App\Repository\ScreenPlaylistRepository;
use App\Utils\ValidationUtils;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class ScreenPlaylistDeleteController extends AbstractController
{
    public function __construct(
        private ScreenPlaylistRepository $screenPlaylistRepository,
        private ValidationUtils $validationUtils
    ) {
    }

    public function __invoke(string $id, string $playlistId): JsonResponse
    {
        $ulid = $this->validationUtils->validateUlid($id);
        $playlistUlid = $this->validationUtils->validateUlid($playlistId);

        $this->screenPlaylistRepository->deleteRelations($ulid, $playlistUlid);

        return new JsonResponse(null, 204);
    }
}
