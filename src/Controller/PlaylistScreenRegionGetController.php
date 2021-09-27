<?php

namespace App\Controller;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Paginator;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use App\Repository\PlaylistScreenRegionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Uid\Ulid;

#[AsController]
class PlaylistScreenRegionGetController extends AbstractController
{
    public function __construct(
        private PlaylistScreenRegionRepository $playlistScreenRegionRepository
    ) {
    }

    public function __invoke(Request $request, string $id, string $regionId): Paginator
    {
        if (!(Ulid::isValid($id) && Ulid::isValid($regionId))) {
            throw new InvalidArgumentException();
        }

        $page = (int) $request->query->get('page', '1');
        $itemsPerPage = (int) $request->query->get('itemsPerPage', '10');

        $screenUlidObj = Ulid::fromString($id);
        $regionUlidObj = Ulid::fromString($regionId);

        return $this->playlistScreenRegionRepository->getPlaylistsByScreenRegion($screenUlidObj, $regionUlidObj, $page, $itemsPerPage);
    }
}
