<?php

namespace App\Controller;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Paginator;
use App\Repository\PlaylistScreenRegionRepository;
use App\Utils\ValidationUtils;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class PlaylistScreenRegionGetController extends AbstractController
{
    public function __construct(
        private PlaylistScreenRegionRepository $playlistScreenRegionRepository,
        private ValidationUtils $validationUtils
    ) {
    }

    public function __invoke(Request $request, string $id, string $regionId): Paginator
    {
        $page = (int) $request->query->get('page', '1');
        $itemsPerPage = (int) $request->query->get('itemsPerPage', '10');

        $screenUlidObj = $this->validationUtils->validateUlid($id);
        $regionUlidObj = $this->validationUtils->validateUlid($regionId);

        return $this->playlistScreenRegionRepository->getPlaylistsByScreenRegion($screenUlidObj, $regionUlidObj, $page, $itemsPerPage);
    }
}
