<?php

namespace App\Controller;

use ApiPlatform\Core\Exception\InvalidArgumentException;
use App\Repository\PlaylistRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Uid\Ulid;

#[AsController]
class PlaylistSlidePutController extends AbstractController
{
    public function __construct(
        private PlaylistRepository $playlistRepository,
        private RequestStack $request
    ) {
    }

    public function __invoke(string $id, string $slideId): JsonResponse
    {
        if (!(Ulid::isValid($id) && Ulid::isValid($slideId))) {
            throw new InvalidArgumentException();
        }

        $ulid = Ulid::fromString($id);
        $slideUlid = Ulid::fromString($slideId);

        $jsonStr = $this->request->getCurrentRequest()->getContent();
        $content = json_decode($jsonStr, true);
        if (is_null($content) || !array_key_exists('weight', $content)) {
            throw new InvalidArgumentException();
        }

        $this->playlistRepository->slideOperation($ulid, $slideUlid, $content['weight'], PlaylistRepository::LINK);

        return new JsonResponse(null, 201);
    }
}
