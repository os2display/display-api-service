<?php

namespace App\Controller;

use ApiPlatform\Core\Exception\InvalidArgumentException;
use App\Repository\PlaylistScreenRegionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Uid\Ulid;

#[AsController]
class PlaylistScreenRegionDeleteController extends AbstractController
{
    public function __construct(
        private PlaylistScreenRegionRepository $playlistScreenRegionRepository
    ) {
    }

    public function __invoke(Request $request, string $id, string $regionId, string $playlistId): JsonResponse
    {
        if (!(Ulid::isValid($id) && Ulid::isValid($regionId) && Ulid::isValid($playlistId))) {
            throw new InvalidArgumentException();
        }

        $screenUlid = Ulid::fromString($id);
        $regionUlid = Ulid::fromString($regionId);
        $playlistUlid = Ulid::fromString($playlistId);

        $this->playlistScreenRegionRepository->unlinkPlaylistsByScreenRegion($screenUlid, $regionUlid, $playlistUlid);

        return new JsonResponse(null, 204);
    }
}
