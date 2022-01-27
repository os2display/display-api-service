<?php

namespace App\Controller;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Paginator;
use App\Repository\ScreenCampaignRepository;
use App\Utils\ValidationUtils;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class CampaignScreenGetController extends AbstractController
{
    public function __construct(
        private ScreenCampaignRepository $screenCampaignRepository,
        private ValidationUtils $validationUtils
    ) {
    }

    public function __invoke(Request $request, string $id): Paginator
    {
        $page = (int) $request->query->get('page', '1');
        $itemsPerPage = (int) $request->query->get('itemsPerPage', '10');

        $screenUlidObj = $this->validationUtils->validateUlid($id);

        return $this->screenCampaignRepository->getSlidePaginator($screenUlidObj, $page, $itemsPerPage);
    }
}
