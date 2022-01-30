<?php

namespace App\Controller;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Paginator;
use App\Repository\PlaylistRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class CampaignGetController extends AbstractController
{
    public function __construct(
        private PlaylistRepository $playlistRepository,
    ) {
    }

    public function __invoke(Request $request): Paginator
    {
        $page = (int) $request->query->get('page', '1');
        $itemsPerPage = (int) $request->query->get('itemsPerPage', '10');

        return $this->playlistRepository->getCampaigns($page, $itemsPerPage);
    }
}
