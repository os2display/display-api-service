<?php

namespace App\Controller;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Paginator;
use App\Repository\ScreenGroupCampaignRepository;
use App\Utils\ValidationUtils;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class CampaignScreenGroupGetController extends AbstractController
{
    public function __construct(
        private ScreenGroupCampaignRepository $screenGroupCampaignRepository,
        private ValidationUtils $validationUtils
    ) {
    }

    public function __invoke(Request $request, string $id): Paginator
    {
        $page = (int) $request->query->get('page', '1');
        $itemsPerPage = (int) $request->query->get('itemsPerPage', '10');

        $playlistUlidObj = $this->validationUtils->validateUlid($id);

        return $this->screenGroupCampaignRepository->getCampaignPaginator($playlistUlidObj, $page, $itemsPerPage);
    }
}
