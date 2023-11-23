<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\ScreenCampaignRepository;
use App\Utils\ValidationUtils;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class ScreenCampaignDeleteController extends AbstractController
{
    public function __construct(
        private readonly ScreenCampaignRepository $screenCampaignRepository,
        private readonly ValidationUtils $validationUtils
    ) {}

    public function __invoke(string $id, string $campaignId): JsonResponse
    {
        $ulid = $this->validationUtils->validateUlid($id);
        $campaignUlid = $this->validationUtils->validateUlid($campaignId);

        $this->screenCampaignRepository->deleteRelations($ulid, $campaignUlid);

        return new JsonResponse(null, 204);
    }
}
