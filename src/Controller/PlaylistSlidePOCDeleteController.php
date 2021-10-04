<?php

namespace App\Controller;

use ApiPlatform\Core\Exception\InvalidArgumentException;
use App\Repository\PlaylistSlideRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Uid\Ulid;

#[AsController]
class PlaylistSlidePOCDeleteController extends AbstractController
{
    public function __construct(
        private PlaylistSlideRepository $playlistSlideRepository,
    ) {
    }

    public function __invoke(string $id, string $slideId): JsonResponse
    {
        if (!(Ulid::isValid($id) && Ulid::isValid($slideId))) {
            throw new InvalidArgumentException();
        }

        $ulid = Ulid::fromString($id);
        $slideUlid = Ulid::fromString($slideId);

        $this->playlistSlideRepository->deleteRelations($ulid, $slideUlid);

        return new JsonResponse(null, 204);
    }
}
