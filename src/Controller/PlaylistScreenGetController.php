<?php

namespace App\Controller;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Paginator;
use App\Repository\PlaylistRepository;
use App\Utils\ValidationUtils;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class PlaylistScreenGetController extends AbstractController
{
    public function __construct(
        private PlaylistRepository $playlistRepository,
        private ValidationUtils $validationUtils
    ) {
    }

    public function __invoke(Request $request, string $id): Paginator
    {
        $page = (int) $request->query->get('page', '1');
        $itemsPerPage = (int) $request->query->get('itemsPerPage', '10');

        $playListUlidObj = $this->validationUtils->validateUlid($id);

        return $this->playlistRepository->getScreenPaginator($playListUlidObj, $page, $itemsPerPage);
    }
}
