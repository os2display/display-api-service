<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\ScreenCampaignRepository;
use App\Utils\ValidationUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class ScreenCampaignDeleteController extends AbstractTenantAwareController
{
    public function __construct(
        private readonly ScreenCampaignRepository $screenCampaignRepository,
        private readonly ValidationUtils $validationUtils
    ) {}

    public function __invoke(string $id, string $campaignId): JsonResponse
    {
        $ulid = $this->validationUtils->validateUlid($id);
        $campaignUlid = $this->validationUtils->validateUlid($campaignId);

        $this->screenCampaignRepository->deleteRelations($ulid, $campaignUlid, $this->getActiveTenant());

        return new JsonResponse(null, \Symfony\Component\HttpFoundation\Response::HTTP_NO_CONTENT);
    }
}
