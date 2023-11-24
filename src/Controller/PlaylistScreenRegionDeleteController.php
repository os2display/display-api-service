<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\PlaylistScreenRegionRepository;
use App\Utils\ValidationUtils;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class PlaylistScreenRegionDeleteController extends AbstractController
{
    public function __construct(
        private readonly PlaylistScreenRegionRepository $playlistScreenRegionRepository,
        private readonly ValidationUtils $validationUtils
    ) {}

    public function __invoke(string $id, string $regionId, string $playlistId): JsonResponse
    {
        $screenUlid = $this->validationUtils->validateUlid($id);
        $regionUlid = $this->validationUtils->validateUlid($regionId);
        $playlistUlid = $this->validationUtils->validateUlid($playlistId);

        $this->playlistScreenRegionRepository->deleteRelations($screenUlid, $regionUlid, $playlistUlid);

        return new JsonResponse(null, \Symfony\Component\HttpFoundation\Response::HTTP_NO_CONTENT);
    }
}
