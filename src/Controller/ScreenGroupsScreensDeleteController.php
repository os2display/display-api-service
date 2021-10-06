<?php

namespace App\Controller;

use ApiPlatform\Core\Exception\InvalidArgumentException;
use App\Repository\ScreenGroupRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Uid\Ulid;

#[AsController]
class ScreenGroupsScreensDeleteController extends AbstractController
{
    public function __construct(
        private ScreenGroupRepository $screenGroupRepository,
    ) {
    }

    public function __invoke(string $id, string $screenGroupId): JsonResponse
    {
        if (!(Ulid::isValid($id) && Ulid::isValid($screenGroupId))) {
            throw new InvalidArgumentException();
        }

        $ulid = Ulid::fromString($id);
        $screenGroupUlid = Ulid::fromString($screenGroupId);

        $this->screenGroupRepository->deleteRelations($ulid, $screenGroupUlid);

        return new JsonResponse(null, 204);
    }
}
